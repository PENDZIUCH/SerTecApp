<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\EquipmentController;
use App\Http\Controllers\Api\V1\PartController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VisitController;
use App\Http\Controllers\Api\V1\WorkOrderController;
use App\Http\Controllers\Api\V1\WorkshopController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::apiResource('users', UserController::class);
        
        Route::apiResource('customers', CustomerController::class);
        
        Route::apiResource('equipments', EquipmentController::class);
        Route::post('equipments/{equipment}/change-status', [EquipmentController::class, 'changeStatus']);
        
        Route::apiResource('work-orders', WorkOrderController::class);
        Route::post('work-orders/{workOrder}/change-status', [WorkOrderController::class, 'changeStatus']);
        Route::post('work-orders/{workOrder}/parts', [WorkOrderController::class, 'addPart']);
        
        Route::apiResource('parts', PartController::class);
        Route::post('parts/{part}/movements', [PartController::class, 'addMovement']);
        
        Route::apiResource('visits', VisitController::class);
        Route::post('visits/{visit}/check-in', [VisitController::class, 'checkIn']);
        Route::post('visits/{visit}/check-out', [VisitController::class, 'checkOut']);
        
        Route::apiResource('subscriptions', SubscriptionController::class);
        Route::post('subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew']);
        
        Route::apiResource('workshop', WorkshopController::class);
        Route::post('workshop/{workshop}/change-status', [WorkshopController::class, 'changeStatus']);
        
        Route::apiResource('budgets', BudgetController::class);
        Route::post('budgets/{budget}/approve', [BudgetController::class, 'approve']);
        Route::post('budgets/{budget}/reject', [BudgetController::class, 'reject']);
    });
});
