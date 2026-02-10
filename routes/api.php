<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/refresh', [UserController::class, 'refresh']);

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Patients
    Route::apiResource('patients', PatientController::class);

    // Services
    Route::apiResource('services', ServiceController::class);

    // Appointments
    Route::apiResource('appointments', AppointmentController::class);

    // Medical Records
    Route::apiResource('medical-records', MedicalRecordController::class);

    // Doctor Availabilities
    Route::apiResource('doctor-availabilities', DoctorAvailabilityController::class);

    // Products
    Route::apiResource('products', ProductController::class);

    // Stock Movements (immutable: index, store, show only)
    Route::apiResource('stock-movements', StockMovementController::class)->only(['index', 'store', 'show']);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);

    // Invoice Items (nested under invoices)
    Route::apiResource('invoices.items', InvoiceItemController::class)->except(['index']);

    // Invoice Payments (nested under invoices)
    Route::apiResource('invoices.payments', InvoicePaymentController::class)->only(['store', 'show', 'destroy']);
});
