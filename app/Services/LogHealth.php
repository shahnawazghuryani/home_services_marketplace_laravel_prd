<?php

namespace App\Services;

class LogHealth
{
    public function summary(): array
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            return [
                'has_errors' => false,
                'entries' => [],
                'message' => 'Laravel log file not found yet.',
            ];
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $lines = array_slice($lines, -1 * max(1, (int) config('launch.monitoring.dashboard_log_lines', 40)));

        $entries = [];
        foreach ($lines as $line) {
            if (! preg_match('/\.(ERROR|CRITICAL|ALERT|EMERGENCY):/i', $line, $matches)) {
                continue;
            }

            $entries[] = [
                'level' => strtoupper($matches[1]),
                'line' => $line,
            ];
        }

        return [
            'has_errors' => $entries !== [],
            'entries' => array_slice($entries, -5),
            'message' => $entries === [] ? 'No recent error-level log entries.' : null,
        ];
    }
}
