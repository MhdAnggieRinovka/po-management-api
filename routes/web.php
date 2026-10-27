<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\CommitmentController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function () {
    Route::get('/csrf-token', fn (Request $request) => response()->json([
        'csrf_token' => csrf_token(),
    ]));

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::middleware('role:admin,sales,warehouse')->group(function () {
            Route::get('/inventory', [InventoryController::class, 'index']);
            Route::get('/commitments', [CommitmentController::class, 'index']);
            Route::get('/samples', [SampleController::class, 'index']);
        });
        Route::middleware('role:admin,sales')->group(function () {
            Route::post('/commitments', [CommitmentController::class, 'store']);
            Route::post('/commitments/{commitment}/cancel', [CommitmentController::class, 'cancel']);
            Route::post('/samples', [SampleController::class, 'store']);
            Route::post('/samples/{sample}/cancel', [SampleController::class, 'cancel']);
        });
        Route::middleware('role:admin,warehouse')->group(function () {
            Route::post('/commitments/{commitment}/ship', [CommitmentController::class, 'ship']);
            Route::post('/samples/{sample}/issue', [SampleController::class, 'issue']);
        });

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::patch('/notifications/{notificationId}/read', [NotificationController::class, 'markRead']);

        Route::get('/google-calendar/status', [GoogleCalendarController::class, 'status']);
        Route::delete('/google-calendar/connection', [GoogleCalendarController::class, 'disconnect']);

        Route::middleware('role:admin,sales,warehouse')->group(function () {
            Route::get('/suppliers', [SupplierController::class, 'index']);
            Route::get('/suppliers/{supplier}', [SupplierController::class, 'show']);
            Route::get('/products', [ProductController::class, 'index']);
            Route::get('/products/{product}', [ProductController::class, 'show']);
            Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
            Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
        });

        Route::middleware('role:admin')->group(function () {
            Route::post('/suppliers', [SupplierController::class, 'store']);
            Route::put('/suppliers/{supplier}', [SupplierController::class, 'update']);
            Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update']);
            Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);
            Route::post('/products', [ProductController::class, 'store']);
            Route::put('/products/{product}', [ProductController::class, 'update']);
            Route::patch('/products/{product}', [ProductController::class, 'update']);
            Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        });

        Route::middleware('role:admin,sales')->group(function () {
            Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);
            Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
        });

        Route::middleware('role:admin,warehouse')->group(function () {
            Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
        });
    });
});
