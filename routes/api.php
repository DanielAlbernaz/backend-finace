<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FinancialReleaseController;
use App\Http\Controllers\RevenueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AuthController::class)
    ->prefix('auth')
    ->group(function() {
        Route::post('login', 'login');
        Route::post('logout', 'logout');
        Route::post('register','register');
        Route::post('logout', 'logout');
        // Route::get('me', 'me');
});



Route::group(
[
    'middleware' => ['auth:sanctum'],
],
function () {

    Route::get('auth/me', [AuthController::class, 'me']);

    // Rota de listagem com suporte a GET e POST para filtros
    Route::match(['get', 'post'], 'financial_release', [FinancialReleaseController::class, 'index']);

    // Rotas REST padrão (exceto index que já foi definida acima)
    Route::post('financial_release', [FinancialReleaseController::class, 'store']);

    // Rotas adicionais para financial_release (devem vir ANTES das rotas com {financialRelease})
    Route::get('financial_release/totals', [FinancialReleaseController::class, 'getTotals']);
    Route::get('financial_release/upcoming-due', [FinancialReleaseController::class, 'getUpcomingDue']);
    Route::get('financial_release/latest', [FinancialReleaseController::class, 'getLatest']);

    // Rotas para parcelamentos (installments) - DEVEM vir ANTES das rotas com {financialRelease}
    Route::get('financial_release/installments', [FinancialReleaseController::class, 'listInstallments']);
    Route::get('financial_release/installments/{installmentId}', [FinancialReleaseController::class, 'getInstallmentDetails']);

    // Endpoint específico para cancelar uma parcela (deve vir ANTES das rotas com {financialRelease})
    Route::post('financial_release/cancel', [FinancialReleaseController::class, 'cancel']);

    // Rotas REST com parâmetros (devem vir DEPOIS das rotas específicas)
    Route::get('financial_release/{financialRelease}', [FinancialReleaseController::class, 'show']);
    Route::put('financial_release/{financialRelease}', [FinancialReleaseController::class, 'update']);
    Route::patch('financial_release/{financialRelease}', [FinancialReleaseController::class, 'update']);
    Route::delete('financial_release/{financialRelease}', [FinancialReleaseController::class, 'destroy']);
    Route::post('financial_release/{financialRelease}/cancel', [FinancialReleaseController::class, 'cancelSingle']);

    // Rotas para categorias
    Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);
    Route::match(['post', 'patch'], 'categories/{category}/disable', [\App\Http\Controllers\CategoryController::class, 'disable']);

    // Rotas para formas de pagamento
    Route::get('payment-methods', [\App\Http\Controllers\PaymentMethodController::class, 'index']);
    Route::get('payment-methods/{paymentMethod}', [\App\Http\Controllers\PaymentMethodController::class, 'show']);
    Route::post('payment-methods', [\App\Http\Controllers\PaymentMethodController::class, 'store']);
    Route::put('payment-methods/{paymentMethod}', [\App\Http\Controllers\PaymentMethodController::class, 'update']);
    Route::patch('payment-methods/{paymentMethod}', [\App\Http\Controllers\PaymentMethodController::class, 'update']);
    Route::match(['post', 'patch'], 'payment-methods/{paymentMethod}/disable', [\App\Http\Controllers\PaymentMethodController::class, 'disable']);
    Route::delete('payment-methods/{paymentMethod}', [\App\Http\Controllers\PaymentMethodController::class, 'destroy']);

    // Rotas para gerenciar convites
    Route::prefix('finance-accounts/{financeAccount}')->group(function () {
        Route::get('invites', [\App\Http\Controllers\FinanceAccountInviteController::class, 'index']);
        Route::post('invites', [\App\Http\Controllers\FinanceAccountInviteController::class, 'store']);
        Route::delete('invites/{invite}', [\App\Http\Controllers\FinanceAccountInviteController::class, 'destroy']);
    });

    // Rotas para logs de atividade
    Route::get('activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index']);
    Route::get('activity-logs/{type}/{id}', [\App\Http\Controllers\ActivityLogController::class, 'getLogsByRecord']);

});

    Route::get('teste', [FinancialReleaseController::class, 'teste']);



