<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/payments', [PaymentController::class, 'savePaymentRecord']);
Route::post('/payments/upload', [FileController::class, 'uploadFile']);
Route::get('/payments', [PaymentController::class, 'getPaginatedPaymentRecords']);

