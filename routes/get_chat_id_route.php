<?php
/**
 * ВРЕМЕННЫЙ РОУТ для получения chat_id через отправку сообщения
 * 
 * Добавьте это в routes/web.php временно:
 * require __DIR__ . '/get_chat_id_route.php';
 * 
 * Затем удалите этот файл после использования!
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/admin/get-chat-id', function () {
    $botToken = config('services.telegram.bot_token');
    
    if (!$botToken) {
        return response()->json(['error' => 'Bot token not configured'], 500);
    }
    
    $username = request('username');
    
    if (!$username) {
        return view('get_chat_id_form');
    }
    
    $username = ltrim($username, '@');
    
    // Пробуем разные форматы
    $formatsToTry = ["@{$username}", $username];
    $results = [];
    
    foreach ($formatsToTry as $format) {
        try {
            $response = Http::timeout(10)
                ->withoutVerifying(app()->environment('local') ? false : true)
                ->get("https://api.telegram.org/bot{$botToken}/getChat", [
                    'chat_id' => $format
                ]);
            
            $data = $response->json();
            
            if ($response->successful() && isset($data['ok']) && $data['ok']) {
                $chat = $data['result'];
                return response()->json([
                    'success' => true,
                    'chat_id' => $chat['id'] ?? null,
                    'type' => $chat['type'] ?? 'unknown',
                    'title' => $chat['title'] ?? $chat['first_name'] ?? $username,
                    'username' => $chat['username'] ?? null,
                    'format_used' => $format
                ]);
            } else {
                $results[] = [
                    'format' => $format,
                    'error' => $data['description'] ?? 'Unknown error'
                ];
            }
        } catch (\Exception $e) {
            $results[] = [
                'format' => $format,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Канал не найден',
        'tried_formats' => $results,
        'suggestion' => 'Попробуйте использовать скрипт get_chat_id_from_updates.php после отправки сообщения в канал'
    ], 404);
})->name('admin.get-chat-id');

