<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemHealthController extends Controller
{
    public function index()
    {
        // ── Queue stats ───────────────────────────────────────────────────────
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs  = DB::table('failed_jobs')->count();

        $recentFailed = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(10)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'id'          => $job->id,
                    'queue'       => $job->queue,
                    'job'         => $payload['displayName'] ?? 'Unknown',
                    'exception'   => $this->firstLine($job->exception),
                    'failed_at'   => $job->failed_at,
                ];
            });

        // ── Recent error log entries ──────────────────────────────────────────
        $logFile  = storage_path('logs/laravel.log');
        $logLines = [];
        if (file_exists($logFile)) {
            // Read last 100 KB of the log file to avoid memory issues
            $fh   = fopen($logFile, 'r');
            $size = filesize($logFile);
            $read = min($size, 100 * 1024);
            fseek($fh, max(0, $size - $read));
            $content = fread($fh, $read);
            fclose($fh);

            // Extract ERROR/CRITICAL lines (each log entry starts with [timestamp])
            preg_match_all('/\[[\d\-T: +]+\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY):.*$/m', $content, $matches);
            $logLines = array_reverse(array_slice($matches[0], -20));
        }

        // ── System info ───────────────────────────────────────────────────────
        $sysInfo = [
            'php_version'    => PHP_VERSION,
            'laravel_version'=> app()->version(),
            'env'            => config('app.env'),
            'debug'          => config('app.debug') ? 'ON' : 'OFF',
            'cache_driver'   => config('cache.default'),
            'queue_driver'   => config('queue.default'),
            'db_driver'      => config('database.default'),
            'timezone'       => config('app.timezone'),
        ];

        // ── Disk usage ────────────────────────────────────────────────────────
        try {
            $storagePath = storage_path();
            $diskFree  = disk_free_space($storagePath);
            $diskTotal = disk_total_space($storagePath);
            $diskUsed  = $diskTotal - $diskFree;
            $diskPct   = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0;
        } catch (\Throwable) {
            $diskFree = $diskTotal = $diskUsed = $diskPct = null;
        }

        $disk = compact('diskFree', 'diskTotal', 'diskUsed', 'diskPct');

        // ── Log file size ─────────────────────────────────────────────────────
        $logSize = file_exists($logFile) ? filesize($logFile) : 0;

        // ── Cache test ────────────────────────────────────────────────────────
        $cacheOk = false;
        try {
            Cache::put('_health_check', 1, 5);
            $cacheOk = Cache::get('_health_check') === 1;
        } catch (\Throwable) {}

        // ── DB connectivity ───────────────────────────────────────────────────
        $dbOk = false;
        $dbMs = null;
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbMs = round((microtime(true) - $start) * 1000, 2);
            $dbOk = true;
        } catch (\Throwable) {}

        return view('superadmin.health', compact(
            'pendingJobs', 'failedJobs', 'recentFailed',
            'logLines', 'sysInfo', 'disk', 'logSize', 'cacheOk', 'dbOk', 'dbMs'
        ));
    }

    public function clearFailedJobs()
    {
        DB::table('failed_jobs')->truncate();
        return back()->with('success', 'Failed jobs cleared.');
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        return back()->with('success', 'All caches cleared successfully.');
    }

    public function optimizeAll()
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        Artisan::call('event:cache');
        return back()->with('success', 'Application optimized: config, routes, views, and events have been cached.');
    }

    private function firstLine(string $text): string
    {
        $lines = explode("\n", trim($text));
        return mb_substr($lines[0] ?? '', 0, 200);
    }
}
