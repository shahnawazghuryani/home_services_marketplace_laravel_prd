<?php

use App\Http\Controllers\Api\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/landing', [LandingController::class, 'index']);
