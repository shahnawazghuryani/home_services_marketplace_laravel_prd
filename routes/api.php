<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\Api\LandingController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/landing', [LandingController::class, 'index']);
Route::post('/ai/booking-helper', [AiController::class, 'bookingHelper']);
Route::post('/ai/service-builder', [AiController::class, 'providerServiceBuilder']);
Route::post('/ai/provider-recommendations', [AiController::class, 'providerRecommendations']);

Route::post('/deploy/webhook', function () {
    $expectedSecret = env('DEPLOY_WEBHOOK_SECRET');
    $providedSecret = (string) request()->header('X-Deploy-Secret', '');

    abort_unless(
        $expectedSecret && hash_equals((string) $expectedSecret, $providedSecret),
        403,
        'Invalid deploy secret.'
    );

    $commands = [
        'migrate' => ['--force' => true],
        'storage:link' => [],
        'optimize:clear' => [],
    ];

    $result = [];
    foreach ($commands as $command => $options) {
        try {
            Artisan::call($command, $options);
            $result[] = [
                'command' => $command,
                'status' => 'ok',
                'output' => trim(Artisan::output()),
            ];
        } catch (\Throwable $exception) {
            $result[] = [
                'command' => $command,
                'status' => 'error',
                'output' => $exception->getMessage(),
            ];
        }
    }

    return response()->json([
        'message' => 'Deploy webhook executed.',
        'result' => $result,
    ]);
});
