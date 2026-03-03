<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CashShiftController;
// use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\HealthInsuranceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StockMovementController;
// use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserInvitationController;
use App\Http\Controllers\VoucherTypeController;
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

    // ── Doctors (Convenience Endpoint) ──────────────────────────────────
    // Aliases to UserController@index with role=doctor filter pre-applied.
    Route::middleware('role:clinic_manager,receptionist,doctor')->group(function () {
        Route::get('/doctors', [UserController::class, 'index'])->defaults('role', 'doctor');
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

    // ── Health Insurances (Obras Sociales) ──────────────────────────────
    // View: All authenticated roles.
    // Manage: Clinic Manager.
    Route::middleware('role:clinic_manager,receptionist,doctor')->group(function () {
        Route::get('/health-insurances', [HealthInsuranceController::class, 'index']);
    });
    Route::middleware('role:clinic_manager')->group(function () {
        Route::post('/health-insurances', [HealthInsuranceController::class, 'store']);
        Route::put('/health-insurances/{healthInsurance}', [HealthInsuranceController::class, 'update']);
        Route::delete('/health-insurances/{healthInsurance}', [HealthInsuranceController::class, 'destroy']);
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

    // ── Brands, Categories, Subcategories ─────────────────────────────
    // View: Clinic Manager, Receptionist.
    // Manage: Clinic Manager.
    Route::middleware('role:clinic_manager,receptionist')->group(function () {
        Route::get('/brands', [BrandController::class, 'index']);
        // Route::get('/categories', [CategoryController::class, 'index']);
        // Route::get('/subcategories', [SubcategoryController::class, 'index']);
    });
    Route::middleware('role:clinic_manager')->group(function () {
        Route::post('/brands', [BrandController::class, 'store']);
        Route::put('/brands/{brand}', [BrandController::class, 'update']);
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy']);
        // Route::post('/categories', [CategoryController::class, 'store']);
        // Route::put('/categories/{category}', [CategoryController::class, 'update']);
        // Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
        // Route::post('/subcategories', [SubcategoryController::class, 'store']);
        // Route::put('/subcategories/{subcategory}', [SubcategoryController::class, 'update']);
        // Route::delete('/subcategories/{subcategory}', [SubcategoryController::class, 'destroy']);
    });

    // ── Products ────────────────────────────────────────────────────────
    // View: Clinic Manager, Receptionist.
    // Create/Delete (archive): Clinic Manager, Receptionist.
    // Update/Restore/Import: Clinic Manager only.
    Route::middleware('role:clinic_manager,receptionist')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/kpis', [ProductController::class, 'kpis']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        // Receptionist can create new products and soft-delete (archive) them
        Route::post('/products', [ProductController::class, 'store']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });
    Route::middleware('role:clinic_manager')->group(function () {
        Route::post('/products/import', [ProductController::class, 'import']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::patch('/products/{id}/restore', [ProductController::class, 'restore']);
    });

    // ── Stock Movements ─────────────────────────────────────────────────
    // View/Create: Clinic Manager, Receptionist.
    Route::middleware('role:clinic_manager,receptionist')->group(function () {
        Route::get('/stock-movements', [StockMovementController::class, 'index']);
        Route::get('/stock-movements/adjustment-reasons', [ProductController::class, 'adjustmentReasons']);
        Route::get('/stock-movements/{stock_movement}', [StockMovementController::class, 'show']);
        Route::post('/products/{product}/movements', [StockMovementController::class, 'store']);
    });

    // ── Cash Shifts (Caja Diaria) ───────────────────────────────────────
    // Manage: Clinic Manager, Receptionist.
    Route::middleware('role:clinic_manager,receptionist')->group(function () {
        Route::get('/cash-shifts', [CashShiftController::class, 'index']);
        Route::get('/cash-shifts/current', [CashShiftController::class, 'current']);
        Route::post('/cash-shifts/open', [CashShiftController::class, 'open']);
        Route::post('/cash-shifts/close', [CashShiftController::class, 'close']);
    });

    // ── Payment Methods & Voucher Types ─────────────────────────────────
    // View: Clinic Manager, Receptionist.
    Route::middleware('role:clinic_manager,receptionist')->group(function () {
        Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
        Route::get('/voucher-types', [VoucherTypeController::class, 'index']);
    });

    // ── Invoices ────────────────────────────────────────────────────────
    // View: Clinic Manager, Receptionist.
    // Create: Clinic Manager, Receptionist (POS).
    // Manage (Update/Delete): Clinic Manager.
    Route::middleware('role:clinic_manager,receptionist')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('/invoices/{invoice}/history', [InvoiceController::class, 'history']);
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
