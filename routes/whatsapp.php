<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsApp\WhatsAppSendController;
use App\Http\Controllers\WhatsApp\WhatsAppAiController;

/*
|--------------------------------------------------------------------------
| WhatsApp Internal API Routes
|--------------------------------------------------------------------------
|
| These routes handle internal API endpoints for sending messages and
| testing AI. Webhook routes are defined in routes/api.php.
|
*/

// Internal API routes (requires authentication)
Route::middleware(['auth:sanctum'])->prefix('api/whatsapp')->group(function () {

    // Send messages
    Route::post('/send/text', [WhatsAppSendController::class, 'sendText'])
        ->name('whatsapp.send.text');

    Route::post('/send/buttons', [WhatsAppSendController::class, 'sendButtons'])
        ->name('whatsapp.send.buttons');

    Route::post('/send/template', [WhatsAppSendController::class, 'sendTemplate'])
        ->name('whatsapp.send.template');

    // Conversation management
    Route::get('/conversations/{conversationId}/messages', [WhatsAppSendController::class, 'getMessages'])
        ->name('whatsapp.conversations.messages');

    Route::patch('/conversations/{conversationId}/status', [WhatsAppSendController::class, 'updateStatus'])
        ->name('whatsapp.conversations.update-status');

    // AI testing endpoints
    Route::post('/ai/test-process', [WhatsAppAiController::class, 'testProcess'])
        ->name('whatsapp.ai.test-process');

    Route::post('/ai/test-prompt', [WhatsAppAiController::class, 'testPrompt'])
        ->name('whatsapp.ai.test-prompt');

    Route::post('/ai/execute-action', [WhatsAppAiController::class, 'executeAction'])
        ->name('whatsapp.ai.execute-action');

    Route::get('/ai/status', [WhatsAppAiController::class, 'status'])
        ->name('whatsapp.ai.status');

    Route::get('/conversations/{conversationId}/state', [WhatsAppAiController::class, 'getConversationState'])
        ->name('whatsapp.conversations.state');
});
