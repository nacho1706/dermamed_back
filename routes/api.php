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

// Protected routes (all require JWT authentication)
Route::middleware('auth:api')->group(function () {

    // Auth (all authenticated users)
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/refresh', [UserController::class, 'refresh']);

    // Users: list/view for admin & receptionist; modify/delete admin only
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
    });
    Route::middleware('role:admin')->group(function () {
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });

    // Patients: full CRUD for admin & receptionist; read-only for doctors
    Route::middleware('role:admin,receptionist,doctor')->group(function () {
        Route::get('/patients', [PatientController::class, 'index']);
        Route::get('/patients/{patient}', [PatientController::class, 'show']);
    });
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::post('/patients', [PatientController::class, 'store']);
        Route::put('/patients/{patient}', [PatientController::class, 'update']);
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy']);
    });

    // Services: all authenticated users can view; admin manages
    Route::middleware('role:admin,receptionist,doctor')->group(function () {
        Route::get('/services', [ServiceController::class, 'index']);
        Route::get('/services/{service}', [ServiceController::class, 'show']);
    });
    Route::middleware('role:admin')->group(function () {
        Route::post('/services', [ServiceController::class, 'store']);
        Route::put('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
    });

    // Appointments: read for all; create/delete for admin & receptionist;
    // update for admin, receptionist & doctor (doctor updates status to "attended")
    Route::middleware('role:admin,receptionist,doctor')->group(function () {
        Route::get('/appointments', [AppointmentController::class, 'index']);
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);
    });
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::post('/appointments', [AppointmentController::class, 'store']);
        Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);
    });

    // Medical Records: admin & doctor only (privacy — receptionist cannot access)
    Route::middleware('role:admin,doctor')->group(function () {
        Route::apiResource('medical-records', MedicalRecordController::class);
    });

    // Doctor Availabilities: view for all; manage for admin & doctor
    Route::middleware('role:admin,receptionist,doctor')->group(function () {
        Route::get('/doctor-availabilities', [DoctorAvailabilityController::class, 'index']);
        Route::get('/doctor-availabilities/{doctor_availability}', [DoctorAvailabilityController::class, 'show']);
    });
    Route::middleware('role:admin,doctor')->group(function () {
        Route::post('/doctor-availabilities', [DoctorAvailabilityController::class, 'store']);
        Route::put('/doctor-availabilities/{doctor_availability}', [DoctorAvailabilityController::class, 'update']);
        Route::delete('/doctor-availabilities/{doctor_availability}', [DoctorAvailabilityController::class, 'destroy']);
    });

    // Products: admin & receptionist
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::apiResource('products', ProductController::class);
    });

    // Stock Movements: admin & receptionist (immutable: index, store, show only)
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::apiResource('stock-movements', StockMovementController::class)->only(['index', 'store', 'show']);
    });

    // Invoices: create/view for admin & receptionist; update/delete admin only
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    });
    Route::middleware('role:admin')->group(function () {
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
    });

    // Invoice Items: admin & receptionist
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::apiResource('invoices.items', InvoiceItemController::class)->except(['index']);
    });

    // Invoice Payments: create/view for admin & receptionist; delete admin only
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::post('/invoices/{invoice}/payments', [InvoicePaymentController::class, 'store']);
        Route::get('/invoices/{invoice}/payments/{payment}', [InvoicePaymentController::class, 'show']);
    });
    Route::middleware('role:admin')->group(function () {
        Route::delete('/invoices/{invoice}/payments/{payment}', [InvoicePaymentController::class, 'destroy']);
    });
});

