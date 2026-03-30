<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\EvidenceController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\OperationsController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('api.token')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('api.token')->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('operations', [OperationsController::class, 'index']);
    Route::get('customers', [CustomerController::class, 'index']);

    Route::get('shipments/options', [ShipmentController::class, 'options']);
    Route::apiResource('shipments', ShipmentController::class)->except(['create', 'edit']);

    Route::get('routes/options', [RouteController::class, 'options']);
    Route::apiResource('routes', RouteController::class)->except(['create', 'edit']);

    Route::get('drivers/options', [DriverController::class, 'options']);
    Route::apiResource('drivers', DriverController::class)->except(['create', 'edit']);

    Route::get('vehicles/options', [VehicleController::class, 'options']);
    Route::apiResource('vehicles', VehicleController::class)->except(['create', 'edit']);

    Route::get('maintenance/options', [MaintenanceController::class, 'options']);
    Route::apiResource('maintenance', MaintenanceController::class)->except(['create', 'edit']);

    Route::apiResource('users', UserController::class)->except(['create', 'edit']);
    Route::get('settings', [SettingsController::class, 'show']);
    Route::put('settings', [SettingsController::class, 'update']);
    Route::get('reports', [ReportController::class, 'index']);
    Route::get('reports/export/csv', [ReportController::class, 'exportCsv']);
    Route::get('reports/export/pdf', [ReportController::class, 'exportPdf']);
    Route::get('evidences', [EvidenceController::class, 'index']);
    Route::get('evidences/{evidence}', [EvidenceController::class, 'show']);
    Route::post('shipments/{shipment}/evidence', [EvidenceController::class, 'store']);
    Route::get('tracking/{trackingCode}', [TrackingController::class, 'show']);
});