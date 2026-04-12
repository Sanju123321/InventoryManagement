<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AppNotification;
use App\Models\Company;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('creator')->latest('sent_at')->paginate(20);
        $companies = Company::where('status', 'active')->orderBy('company_name')->get(['id', 'company_name']);
        return view('superadmin.announcements', compact('announcements', 'companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'body'     => 'required|string|max:5000',
            'target'   => 'required|string',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:in_app,email',
        ]);

        $target   = $request->target;  // 'all', 'plan:free', 'company:5', etc.
        $channels = $request->channels; // ['in_app'] or ['email'] or ['in_app','email']

        // Resolve affected companies
        $companies = $this->resolveTargetCompanies($target);

        $channelStr = implode(',', $channels);

        $announcement = Announcement::create([
            'created_by' => Auth::id(),
            'title'      => $request->title,
            'body'        => $request->body,
            'target'     => $target,
            'channels'   => $channelStr,
        ]);

        $inApp = in_array('in_app', $channels);
        $email = in_array('email', $channels);

        $sent = 0;
        foreach ($companies as $company) {
            if ($inApp) {
                AppNotification::create([
                    'company_id' => $company->id,
                    'type'       => 'announcement',
                    'title'      => $request->title,
                    'message'    => $request->body,
                    'data'       => ['announcement_id' => $announcement->id],
                    'is_read'    => false,
                ]);
            }

            if ($email) {
                $this->sendEmailToCompany($company, $request->title, $request->body);
            }
            $sent++;
        }

        ActivityLogService::log(
            'announcement.sent',
            "Announcement '{$request->title}' sent to {$sent} company(ies) via {$channelStr}.",
            null, null
        );

        return redirect()->route('superadmin.announcements')
            ->with('success', "Announcement sent to {$sent} company(ies) successfully.");
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveTargetCompanies(string $target): \Illuminate\Support\Collection
    {
        if ($target === 'all') {
            return Company::where('status', 'active')->get();
        }

        if (str_starts_with($target, 'plan:')) {
            $plan = substr($target, 5);
            return Company::where('status', 'active')->where('plan', $plan)->get();
        }

        if (str_starts_with($target, 'company:')) {
            $id = (int) substr($target, 8);
            return Company::where('id', $id)->get();
        }

        return collect();
    }

    private function sendEmailToCompany(Company $company, string $subject, string $body): void
    {
        try {
            $admins = User::where('company_id', $company->id)
                ->where('role', 'admin')
                ->whereNotNull('email')
                ->get();

            foreach ($admins as $admin) {
                Mail::send([], [], function ($message) use ($admin, $subject, $body, $company) {
                    $message->to($admin->email, $admin->name)
                        ->subject($subject)
                        ->html(
                            '<div style="font-family:sans-serif;max-width:600px;margin:auto;padding:24px">'
                            . '<h2 style="color:#4e73df">' . e($subject) . '</h2>'
                            . '<p>' . nl2br(e($body)) . '</p>'
                            . '<hr><p style="color:#888;font-size:12px">This message was sent to '
                            . e($company->company_name) . ' by the system administrator.</p>'
                            . '</div>'
                        );
                });
            }
        } catch (\Throwable $e) {
            Log::error('Announcement email failed for company ' . $company->id . ': ' . $e->getMessage());
        }
    }
}
