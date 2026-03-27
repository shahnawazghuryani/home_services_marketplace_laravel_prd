<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\Api\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/landing', [LandingController::class, 'index']);
Route::post('/ai/booking-helper', [AiController::class, 'bookingHelper']);
Route::post('/ai/provider-recommendations', [AiController::class, 'providerRecommendations']);
