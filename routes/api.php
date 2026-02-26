<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserInvitationController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/users/verify-token/{token}', [UserInvitationController::class, 'verify']);
Route::post('/users/activate', [UserInvitationController::class, 'activate']);

// Protected routes (all require JWT authentication)
Route::middleware('auth:api')->group(function () {
    // Auth (all authenticated users)
    Route::get('/me', [UserController::class, 'me']);
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/refresh', [UserController::class, 'refresh']);

    // ── Users (Centralized Management: clinic_manager only) ─────────────
    // Read access: clinic_manager, receptionist, doctor (to see staff/doctors).
    Route::middleware('role:clinic_manager,receptionist,doctor')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
    });
    // Write access: clinic_manager only (invite, update, delete).
    Route::middleware('role:clinic_manager')->group(function () {
        Route::post('/users/invite', [UserInvitationController::class, 'invite']);
        Route::post('/users/{user}/resend-invite', [UserInvitationController::class, 'resend']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    // ── Patients ────────────────────────────────────────────────────────
    // View: Clinic Manager, Doctor, Receptionist.
    // Create/Update/Delete: Clinic Manager, Receptionist, Doctor.
    Route::middleware('role:clinic_manager,doctor,receptionist')->group(function () {
        Route::get('/patients', [PatientController::class, 'index']);
        Route::get('/patients/{patient}', [PatientController::class, 'show']);
    });
    Route::middleware('role:clinic_manager,receptionist,doctor')->group(function () {
        Route::post('/patients/import', [PatientController::class, 'import']);
        Route::post('/patients', [PatientController::class, 'store']);
        Route::put('/patients/{patient}', [PatientController::class, 'update']);
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy']);
    });

    // ── Services ────────────────────────────────────────────────────────
    // View: Clinic Manager, Receptionist, Doctor.
    // Manage: Clinic Manager.
    Route::middleware('role:clinic_manager,receptionist,doctor')->group(function () {
        Route::get('/services', [ServiceController::class, 'index']);
        Route::get('/services/{service}', [ServiceController::class, 'show']);
    });
    Route::middleware('role:clinic_manager')->group(function () {
        Route::post('/services/import', [ServiceController::class, 'import']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::put('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
    });

    // ── Appointments ────────────────────────────────────────────────────
    // View: Clinic Manager, Receptionist, Doctor.
    // Create/Update/Delete: Receptionist, Doctor.
    Route::middleware('role:clinic_manager,receptionist,doctor')->group(function () {
        Route::get('/appointments', [AppointmentController::class, 'index']);
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);
    });
    Route::middleware('role:receptionist,doctor')->group(function () {
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);
        Route::post('/appointments', [AppointmentController::class, 'store']);
        Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);
    });

    // ── Medical Records (Doctor ONLY) ───────────────────────────────────
    Route::middleware('role:doctor')->group(function () {
        Route::apiResource('medical-records', MedicalRecordController::class);
    });

    // ── Doctor Availabilities ───────────────────────────────────────────
    // View: Clinic Manager, Receptionist, Doctor.
    // Manage: Doctor (own), Receptionist.
    Route::middleware('role:clinic_manager,receptionist,doctor')->group(function () {
        Route::get('/doctor-availabilities', [DoctorAvailabilityController::class, 'index']);
        Route::get('/doctor-availabilities/{doctor_availability}', [DoctorAvailabilityController::class, 'show']);
    });
    Route::middleware('role:receptionist,doctor')->group(function () {
        Route::post('/doctor-availabilities', [DoctorAvailabilityController::class, 'store']);
        Route::put('/doctor-availabilities/{doctor_availability}', [DoctorAvailabilityController::class, 'update']);
        Route::delete('/doctor-availabilities/{doctor_availability}', [DoctorAvailabilityController::class, 'destroy']);
    });

    // ── Products ────────────────────────────────────────────────────────
    // View: Clinic Manager, Receptionist.
    // Manage: Clinic Manager.
    Route::middleware('role:clinic_manager,receptionist')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
    });
    Route::middleware('role:clinic_manager')->group(function () {
        Route::post('/products/import', [ProductController::class, 'import']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });

    // ── Stock Movements ─────────────────────────────────────────────────
    // View/Create: Clinic Manager, Receptionist.
    Route::middleware('role:clinic_manager,receptionist')->group(function () {
        Route::apiResource('stock-movements', StockMovementController::class)->only(['index', 'store', 'show']);
    });

    // ── Invoices ────────────────────────────────────────────────────────
    // View: Clinic Manager, Receptionist.
    // Create: Receptionist (POS).
    // Manage (Update/Delete): Clinic Manager.
    Route::middleware('role:clinic_manager,receptionist')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    });
    Route::middleware('role:receptionist')->group(function () {
        Route::post('/invoices', [InvoiceController::class, 'store']);
    });
    Route::middleware('role:clinic_manager')->group(function () {
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
    });

    // ── Invoice Items & Payments ────────────────────────────────────────
    Route::middleware('role:clinic_manager,receptionist')->group(function () {
        Route::post('/invoices/{invoice}/payments', [InvoicePaymentController::class, 'store']);
        Route::get('/invoices/{invoice}/payments/{payment}', [InvoicePaymentController::class, 'show']);
        Route::apiResource('invoices.items', InvoiceItemController::class)->except(['index']);
    });
    Route::middleware('role:clinic_manager')->group(function () {
        Route::delete('/invoices/{invoice}/payments/{payment}', [InvoicePaymentController::class, 'destroy']);
    });
});
