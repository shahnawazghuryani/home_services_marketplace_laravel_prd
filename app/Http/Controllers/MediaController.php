<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function show(Request $request, string $path)
    {
        $normalized = str_replace('\\', '/', ltrim($path, '/'));

        if ($normalized === '' || str_contains($normalized, '..')) {
            abort(404);
        }

        $candidates = array_filter([
            public_path($normalized),
            base_path('public/' . $normalized),
            base_path('deploy_public/' . $normalized),
            isset($_SERVER['DOCUMENT_ROOT']) ? rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized) : null,
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return response()->file($candidate, [
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        }

        abort(404);
    }
}
