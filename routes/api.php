<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\SettlementController;
use App\Http\Controllers\Api\MoMoController;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// MoMo Webhook (no auth - IPN callback from MoMo)
Route::post('/momo/webhook', [MoMoController::class, 'webhook']);

// Protected routes (JWT)
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // Agents
    Route::get('/agents', [\App\Http\Controllers\Api\AgentController::class, 'index']);
    Route::get('/agents/user-agents', [\App\Http\Controllers\Api\AgentController::class, 'userAgents']);
    Route::post('/agents', [\App\Http\Controllers\Api\AgentController::class, 'store'])->middleware('admin');
    Route::get('/agents/{agent}', [\App\Http\Controllers\Api\AgentController::class, 'show']);
    Route::put('/agents/{agent}', [\App\Http\Controllers\Api\AgentController::class, 'update'])->middleware('admin');
    Route::delete('/agents/{agent}', [\App\Http\Controllers\Api\AgentController::class, 'destroy'])->middleware('admin');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/export', [TransactionController::class, 'export']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::put('/transactions/{transaction}/status', [TransactionController::class, 'updateStatus']);

    // Daily Advances
    Route::get('/daily-advances', [SettlementController::class, 'getDailyAdvances']);
    Route::post('/daily-advances/settle', [SettlementController::class, 'settleDailyAdvances']);

    // End of Day Settlement
    Route::get('/settlements/eod', [SettlementController::class, 'getEndOfDaySettlement']);
    Route::post('/settlements/eod', [SettlementController::class, 'saveEndOfDaySettlement']);
    Route::get('/settlements/history', [SettlementController::class, 'getSettlementHistory']);

    // Statistics
    Route::get('/statistics', [\App\Http\Controllers\Api\StatisticsController::class, 'index']);
    Route::get('/statistics/summary', [\App\Http\Controllers\Api\StatisticsController::class, 'summary']);

    // MoMo QR
    Route::post('/momo/generate-qr', [\App\Http\Controllers\Api\MoMoController::class, 'generateQR']);
    Route::post('/momo/callback', [\App\Http\Controllers\Api\MoMoController::class, 'callback']);

    // CCCD Scan (Gemini AI)
    Route::post('/cccd/scan', [TransactionController::class, 'scanCCCD']);

    // Queue (dynamic add + process via queue:work)
    Route::post('/queue/dispatch', [\App\Http\Controllers\Api\QueueController::class, 'dispatch']);
    Route::get('/queue/status', [\App\Http\Controllers\Api\QueueController::class, 'status']);
    Route::post('/queue/clone-trigger', [\App\Http\Controllers\Api\QueueController::class, 'triggerClone']);
    Route::get('/queue/clone-status', [\App\Http\Controllers\Api\QueueController::class, 'cloneStatus']);
});

// Config for frontend
Route::get('/config', function () {
    return response()->json([
        'version' => config('customer_management.version'),
        'default_pos_fee' => config('customer_management.default_pos_fee_percent'),
        'default_agent_fee' => config('customer_management.default_agent_fee_percent'),
        'currency' => 'VND',
        'timezone' => config('customer_management.timezone'),
        'status_values' => config('customer_management.status_values'),
        'amount_suggestions' => config('customer_management.amount_suggestions'),
    ]);
})->middleware('auth:api');
