<?php

use Illuminate\Support\Facades\Route;
use Rrq\Dukpay\Http\Controllers\CallbackController;

Route::post(config('dukpay.webhook.path', 'dukpay/callback'), CallbackController::class)
    ->middleware(config('dukpay.webhook.middleware', []))
    ->name(config('dukpay.webhook.name', 'dukpay.callback'));
