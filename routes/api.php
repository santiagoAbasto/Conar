<?php

use App\Http\Controllers\TelemetryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['detect.bot', 'throttle:120,1'])
    ->post('/telemetry', [TelemetryController::class, 'ingest'])
    ->name('telemetry.ingest');
