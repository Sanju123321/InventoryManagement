<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $monthNum] = array_pad(explode('-', $month), 2, now()->month);

        $records = Attendance::where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->whereYear('work_date', (int) $year)
            ->whereMonth('work_date', (int) $monthNum)
            ->orderByDesc('work_date')
            ->get();

        $today = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();

        $onApprovedLeaveToday = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', today())
            ->whereDate('to_date', '>=', today())
            ->exists();

        $leaveLockedDates = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->get()
            ->flatMap(fn (LeaveRequest $leave) => $leave->dateRange())
            ->unique()
            ->values()
            ->all();

        return view('attendance.index', compact('records', 'today', 'month', 'onApprovedLeaveToday', 'leaveLockedDates'));
    }

    public function store(Request $request)
    {
        $auth = auth()->user();

        $rules = [
            'work_date' => 'required|date|before_or_equal:today',
            'status' => ['required', Rule::in($this->employeeAttendanceStatuses($auth->isAdmin()))],
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ];

        if ($auth->isAdmin()) {
            $rules['user_id'] = [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $auth->company_id)),
            ];
        }

        $validated = $request->validate($rules);

        $employeeId = $auth->isAdmin() ? (int) $validated['user_id'] : $auth->id;
        $workDate = $validated['work_date'];

        if (! $auth->isAdmin() && $workDate !== today()->toDateString()) {
            return back()->with('error', 'You can only mark attendance for today. After 11:59 PM the day is locked.');
        }

        $onLeave = LeaveRequest::where('user_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $workDate)
            ->whereDate('to_date', '>=', $workDate)
            ->exists();

        if ($onLeave) {
            return back()->with('error', 'Cannot mark attendance — approved leave already covers this date.');
        }

        Attendance::updateOrCreate(
            ['user_id' => $employeeId, 'work_date' => $workDate],
            [
                'company_id' => $auth->company_id,
                'status' => $validated['status'],
                'check_in' => $this->checkInTime($validated),
                'check_out' => $validated['check_out'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'marked_by' => $auth->id,
            ]
        );

        ActivityLogService::log(
            'attendance.marked',
            "Attendance marked as {$validated['status']} for user #{$employeeId} on {$workDate}."
        );

        return back()->with('success', 'Attendance saved.');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $this->authorizeOwnAttendance($attendance);

        if ($this->isLockedByApprovedLeave($attendance)) {
            return back()->with('error', 'This date is covered by approved leave. Ask admin if it needs to be changed.');
        }

        if ($this->employeeDayIsLocked($attendance)) {
            return back()->with('error', 'Attendance can only be edited until 11:59 PM on that day.');
        }

        $validated = $request->validate([
            'work_date' => 'required|date|before_or_equal:today',
            'status' => ['required', Rule::in($this->employeeAttendanceStatuses(false))],
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        $duplicate = Attendance::where('user_id', $attendance->user_id)
            ->whereDate('work_date', $validated['work_date'])
            ->where('id', '!=', $attendance->id)
            ->exists();

        if ($duplicate) {
            return back()->with('error', 'Attendance already exists for that date.');
        }

        if ($this->hasApprovedLeaveOn($attendance->user_id, $validated['work_date'])) {
            return back()->with('error', 'Cannot move attendance onto a date with approved leave.');
        }

        if ($validated['work_date'] !== today()->toDateString()) {
            return back()->with('error', 'You can only keep attendance on today until 11:59 PM.');
        }

        $attendance->update([
            'work_date' => $validated['work_date'],
            'status' => $validated['status'],
            'check_in' => $this->checkInTime($validated),
            'check_out' => $validated['check_out'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'marked_by' => auth()->id(),
        ]);

        ActivityLogService::log(
            'attendance.updated',
            auth()->user()->name . " updated attendance for {$validated['work_date']}."
        );

        return back()->with('success', 'Attendance updated.');
    }

    public function destroy(Attendance $attendance)
    {
        $this->authorizeOwnAttendance($attendance);

        if ($this->isLockedByApprovedLeave($attendance)) {
            return back()->with('error', 'This date is covered by approved leave and cannot be deleted.');
        }

        if ($this->employeeDayIsLocked($attendance)) {
            return back()->with('error', 'Attendance can only be deleted until 11:59 PM on that day.');
        }

        $date = $attendance->work_date->format('d-m-Y');
        $attendance->delete();

        ActivityLogService::log(
            'attendance.deleted',
            auth()->user()->name . " deleted attendance for {$date}."
        );

        return back()->with('success', 'Attendance deleted.');
    }

    public function admin(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $monthNum] = array_pad(explode('-', $month), 2, now()->month);

        $employees = User::where('company_id', $companyId)
            ->whereIn('role', ['sales_admin', 'inventory_admin', 'admin'])
            ->orderBy('name')
            ->get();

        $query = Attendance::where('company_id', $companyId)
            ->with('user', 'marker')
            ->whereYear('work_date', (int) $year)
            ->whereMonth('work_date', (int) $monthNum);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $records = $query->orderByDesc('work_date')->orderBy('user_id')->paginate(30)->appends($request->query());

        $pendingLeaves = LeaveRequest::where('company_id', $companyId)->where('status', 'pending')->count();

        $todayRows = Attendance::where('company_id', $companyId)->whereDate('work_date', today());
        $stats = [
            'team' => $employees->count(),
            'present' => (clone $todayRows)->where('status', 'present')->count(),
            'leave' => (clone $todayRows)->whereIn('status', ['leave', 'half_day', 'short_leave'])->count(),
            'pending_leaves' => $pendingLeaves,
        ];

        return view('attendance.admin', compact('records', 'employees', 'month', 'pendingLeaves', 'stats'));
    }

    private function employeeDayIsLocked(Attendance $attendance): bool
    {
        return ! $attendance->work_date->isToday();
    }

    /**
     * @return list<string>
     */
    private function employeeAttendanceStatuses(bool $isAdmin): array
    {
        $statuses = ['present', 'absent', 'half_day'];
        if ($isAdmin) {
            $statuses[] = 'short_leave';
        }

        return $statuses;
    }

    private function authorizeOwnAttendance(Attendance $attendance): void
    {
        abort_unless($attendance->user_id === auth()->id(), 403);
        abort_unless($attendance->company_id === auth()->user()->company_id, 403);
    }

    private function isLockedByApprovedLeave(Attendance $attendance): bool
    {
        if ($attendance->status === 'leave') {
            return true;
        }

        return $this->hasApprovedLeaveOn($attendance->user_id, $attendance->work_date->toDateString());
    }

    private function hasApprovedLeaveOn(int $userId, string $workDate): bool
    {
        return LeaveRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $workDate)
            ->whereDate('to_date', '>=', $workDate)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function checkInTime(array $validated): ?string
    {
        if ($validated['status'] === 'absent') {
            return null;
        }

        return $validated['check_in'] ?? null;
    }
}
