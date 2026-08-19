<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $leaves = LeaveRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('leaves.index', compact('leaves'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $payload = $this->validatedLeavePayload($request);

        if ($error = $this->leaveOverlapError($user->id, $payload['from_date'], $payload['to_date'])) {
            return back()->withInput()->with('error', $error);
        }

        $leave = LeaveRequest::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'from_date' => $payload['from_date'],
            'to_date' => $payload['to_date'],
            'leave_type' => $payload['leave_type'],
            'session' => $payload['session'],
            'start_time' => $payload['start_time'],
            'reason' => $payload['reason'],
            'status' => 'pending',
        ]);

        ActivityLogService::log(
            'leave.applied',
            "{$user->name} applied for {$leave->typeLabel()} from {$leave->from_date->format('d-m-Y')} to {$leave->to_date->format('d-m-Y')}."
        );

        app(NotificationService::class)->notifyLeaveApplied($leave);

        return redirect()->route('leaves.index')->with('success', 'Leave application submitted. Waiting for admin approval.');
    }

    public function update(Request $request, LeaveRequest $leave)
    {
        $this->authorizeOwnLeave($leave);

        if (! $leave->employeeCanModify()) {
            return back()->with('error', 'Leave can only be edited until the last leave date (11:59 PM).');
        }

        $payload = $this->validatedLeavePayload($request);
        $oldDates = $leave->status === 'approved' ? $leave->dateRange() : [];

        if ($error = $this->leaveOverlapError(auth()->id(), $payload['from_date'], $payload['to_date'], $leave->id)) {
            return back()->withInput()->with('error', $error);
        }

        $leave->update([
            'from_date' => $payload['from_date'],
            'to_date' => $payload['to_date'],
            'leave_type' => $payload['leave_type'],
            'session' => $payload['session'],
            'start_time' => $payload['start_time'],
            'reason' => $payload['reason'],
        ]);

        if ($leave->status === 'approved') {
            $this->clearLeaveAttendance($leave, $oldDates);
            $this->writeLeaveAttendance($leave);
        }

        ActivityLogService::log(
            'leave.updated',
            auth()->user()->name . " updated leave #{$leave->id}."
        );

        return back()->with('success', 'Leave application updated.');
    }

    public function destroy(LeaveRequest $leave)
    {
        $this->authorizeOwnLeave($leave);

        if (! $leave->employeeCanModify()) {
            return back()->with('error', 'Leave can only be deleted until the last leave date (11:59 PM).');
        }

        if ($leave->status === 'approved') {
            $this->clearLeaveAttendance($leave, $leave->dateRange());
        }

        $id = $leave->id;
        $leave->delete();

        ActivityLogService::log(
            'leave.deleted',
            auth()->user()->name . " deleted leave #{$id}."
        );

        return back()->with('success', 'Leave application deleted.');
    }

    public function admin(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $month = $request->input('month', now()->format('Y-m'));

        $request->merge(['month' => $month]);

        $leaves = $this->filteredLeaves($request)
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        $pendingCount = LeaveRequest::where('company_id', $companyId)->where('status', 'pending')->count();

        return view('leaves.admin', compact('leaves', 'pendingCount', 'month'));
    }

    public function export(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $request->merge(['month' => $month]);

        $leaves = $this->filteredLeaves($request)
            ->orderBy('from_date')
            ->orderBy('user_id')
            ->get();

        $filename = 'leave_applications_' . str_replace('-', '', $month) . '_' . now()->format('His') . '.csv';

        return response()->streamDownload(function () use ($leaves) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Employee',
                'Role',
                'Type',
                'From',
                'To',
                'Duration',
                'Session / Time',
                'Reason',
                'Status',
                'Reviewed By',
                'Review Note',
                'Applied At',
            ]);

            foreach ($leaves as $leave) {
                fputcsv($handle, [
                    $leave->user->name ?? '',
                    $leave->user?->roleLabel() ?? '',
                    $leave->typeLabel(),
                    $leave->from_date->format('d-m-Y'),
                    $leave->to_date->format('d-m-Y'),
                    $leave->durationLabel(),
                    $leave->timeDetail(),
                    $leave->reason,
                    $leave->statusLabel(),
                    $leave->reviewer->name ?? '',
                    $leave->review_note ?? '',
                    $leave->created_at?->format('d-m-Y H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        $this->authorizeCompanyLeave($leave);
        abort_unless($leave->status === 'pending', 422, 'Only pending leave can be approved.');

        $admin = auth()->user();

        $leave->update([
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'review_note' => $request->input('review_note'),
        ]);

        $notes = $leave->typeLabel();
        if ($leave->timeDetail() !== '') {
            $notes .= ' (' . $leave->timeDetail() . ')';
        }

        $this->writeLeaveAttendance($leave, $notes);

        ActivityLogService::log(
            'leave.approved',
            "Leave #{$leave->id} for {$leave->user->name} approved."
        );

        app(NotificationService::class)->notifyLeaveReviewed($leave);

        return back()->with('success', 'Leave approved. Attendance updated for those dates.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $this->authorizeCompanyLeave($leave);
        abort_unless($leave->status === 'pending', 422, 'Only pending leave can be rejected.');

        $request->validate([
            'review_note' => 'nullable|string|max:500',
        ]);

        $leave->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $request->input('review_note'),
        ]);

        ActivityLogService::log(
            'leave.rejected',
            "Leave #{$leave->id} for {$leave->user->name} rejected."
        );

        app(NotificationService::class)->notifyLeaveReviewed($leave);

        return back()->with('success', 'Leave application rejected.');
    }

    private function filteredLeaves(Request $request): Builder
    {
        $query = LeaveRequest::where('company_id', auth()->user()->company_id)
            ->with('user', 'reviewer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('month')) {
            $start = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $query->whereDate('from_date', '<=', $end->toDateString())
                ->whereDate('to_date', '>=', $start->toDateString());
        }

        return $query;
    }

    private function authorizeCompanyLeave(LeaveRequest $leave): void
    {
        abort_unless($leave->company_id === auth()->user()->company_id, 403);
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    private function authorizeOwnLeave(LeaveRequest $leave): void
    {
        abort_unless($leave->user_id === auth()->id(), 403);
        abort_unless($leave->company_id === auth()->user()->company_id, 403);
    }

    /**
     * @return array{from_date: string, to_date: string, leave_type: string, session: ?string, start_time: ?string, reason: string}
     */
    private function validatedLeavePayload(Request $request): array
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'leave_type' => ['required', Rule::in(LeaveRequest::TYPES)],
            'session' => ['nullable', Rule::in(['morning', 'afternoon'])],
            'start_time' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
        ]);

        $type = $validated['leave_type'];
        $session = null;
        $startTime = null;
        $toDate = $validated['to_date'] ?? null;

        if (in_array($type, ['half_day', 'short'], true)) {
            $toDate = $validated['from_date'];
            if ($type === 'half_day') {
                if (empty($validated['session'])) {
                    throw ValidationException::withMessages(['session' => 'Select morning or afternoon for half day.']);
                }
                $session = $validated['session'];
            }
            if ($type === 'short') {
                if (empty($validated['start_time'])) {
                    throw ValidationException::withMessages(['start_time' => 'Select start time. Short leave is 2 hours from this time.']);
                }
                $startTime = $validated['start_time'];
            }
        } elseif (empty($toDate)) {
            throw ValidationException::withMessages(['to_date' => 'To date is required.']);
        }

        return [
            'from_date' => $validated['from_date'],
            'to_date' => $toDate,
            'leave_type' => $type,
            'session' => $session,
            'start_time' => $startTime,
            'reason' => $validated['reason'],
        ];
    }

    private function leaveOverlapError(int $userId, string $fromDate, string $toDate, ?int $exceptId = null): ?string
    {
        $exists = LeaveRequest::where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where(function ($query) use ($fromDate, $toDate) {
                $query->whereDate('from_date', '<=', $toDate)
                    ->whereDate('to_date', '>=', $fromDate);
            })
            ->exists();

        return $exists ? 'You already have a pending or approved leave covering these dates.' : null;
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function clearLeaveAttendance(LeaveRequest $leave, array $dates): void
    {
        if ($dates === []) {
            return;
        }

        Attendance::where('user_id', $leave->user_id)
            ->whereIn('work_date', $dates)
            ->whereIn('status', ['leave', 'half_day', 'short_leave'])
            ->delete();
    }

    private function writeLeaveAttendance(LeaveRequest $leave, ?string $notes = null): void
    {
        $notes ??= $leave->typeLabel() . ($leave->timeDetail() !== '' ? ' (' . $leave->timeDetail() . ')' : '');

        foreach ($leave->dateRange() as $date) {
            Attendance::updateOrCreate(
                ['user_id' => $leave->user_id, 'work_date' => $date],
                [
                    'company_id' => $leave->company_id,
                    'status' => $leave->attendanceStatus(),
                    'check_in' => null,
                    'check_out' => null,
                    'notes' => $notes,
                    'marked_by' => auth()->id(),
                ]
            );
        }
    }
}
