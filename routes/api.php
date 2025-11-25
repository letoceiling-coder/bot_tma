<?php

use App\Http\Controllers\Api\v1\ChannelController;
use App\Http\Controllers\Api\v1\FolderController;
use App\Http\Controllers\Api\v1\MediaController;
use App\Http\Controllers\Api\v1\PassportAuthController;
use App\Http\Controllers\Api\v1\SubscriptionController;
use App\Http\Controllers\Api\v1\TelegramBotDevController;
use App\Http\Controllers\Api\v1\TelegramBotHelperController;
use App\Http\Controllers\Api\v1\TelegramUserController;
use App\Http\Controllers\Api\v1\WheelSectionController;
use App\Http\Controllers\DeployController;
use Illuminate\Support\Facades\Route;

// Роут для обновления проекта (защищен секретным ключом)
// Доступен по /api/deploy (автоматический префикс /api для routes/api.php)
Route::post('/deploy', [DeployController::class, 'deploy'])->middleware('throttle:10,1');
Route::get('/deploy/status', [DeployController::class, 'status']);

// Channel management routes (требуют auth) - sync ДОЛЖЕН БЫТЬ ПЕРЕД routes с {channel}
Route::post('channels/sync', [ChannelController::class, 'sync'])->name('channels.sync');
Route::delete('channels', [ChannelController::class, 'destroyAll'])->name('channels.destroy-all');

Route::middleware('auth:api')->group(function () {
    Route::get('user', [PassportAuthController::class, 'user']);
    Route::post('user/logout', [PassportAuthController::class, 'logout']);
    Route::put('user/update', [PassportAuthController::class, 'update']);
});

Route::post('user/register', [PassportAuthController::class, 'register']);
Route::post('user/login', [PassportAuthController::class, 'login']);
Route::post('user/forgot-password', [PassportAuthController::class, 'forgotPassword']);

// Subscription routes (без auth, но с валидацией initData)
Route::get('subscriptions/check', [SubscriptionController::class, 'check'])->name('subscriptions.check');
Route::get('subscriptions/channels', [SubscriptionController::class, 'channels'])->name('subscriptions.channels');
Route::post('subscriptions/clear-cache', [SubscriptionController::class, 'clearCache'])->name('subscriptions.clear-cache');

// Bot info routes (без auth)
Route::get('bot/username', [SubscriptionController::class, 'getBotUsername'])->name('bot.username');

// Telegram Users routes (без auth, но с валидацией initData)
Route::post('telegram-users/start', [TelegramUserController::class, 'start'])->name('telegram-users.start');
Route::get('telegram-users/me', [TelegramUserController::class, 'me'])->name('telegram-users.me');
Route::get('telegram-users/tickets', [TelegramUserController::class, 'getTickets'])->name('telegram-users.tickets');
Route::post('telegram-users/spend-ticket', [TelegramUserController::class, 'spendTicket'])->name('telegram-users.spend-ticket');


Route::get('folders/tree/all', [FolderController::class, 'tree'])->name('folders.tree');
Route::post('folders/update-positions', [FolderController::class, 'updatePositions'])->name('folders.update-positions');
Route::apiResource('folders', FolderController::class);

Route::post('media/{id}/restore', [MediaController::class, 'restore'])->name('media.restore');
Route::delete('media/trash/empty', [MediaController::class, 'emptyTrash'])->name('media.trash.empty');
Route::apiResource('media', MediaController::class);

//Route::middleware('auth:api')->group(function () {
    Route::post('wheel-sections/sync', [WheelSectionController::class, 'sync'])->name('wheel-sections.sync');
    Route::get('wheel-sections', [WheelSectionController::class, 'index'])->name('wheel-sections.index');
    Route::post('wheel-sections', [WheelSectionController::class, 'store'])->name('wheel-sections.store');
    Route::get('wheel-sections/{wheelSection}', [WheelSectionController::class, 'show'])->name('wheel-sections.show');
    Route::put('wheel-sections/{wheelSection}', [WheelSectionController::class, 'update'])->name('wheel-sections.update');
    Route::delete('wheel-sections/{wheelSection}', [WheelSectionController::class, 'destroy'])->name('wheel-sections.destroy');
//});
// Channel management routes (требуют auth)
//Route::middleware('auth:api')->group(function () {
    Route::get('channels', [ChannelController::class, 'index'])->name('channels.index');
    Route::post('channels', [ChannelController::class, 'store'])->name('channels.store');
    Route::get('channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
    Route::put('channels/{channel}', [ChannelController::class, 'update'])->name('channels.update');
    Route::delete('channels/{channel}', [ChannelController::class, 'destroy'])->name('channels.destroy');
//});
