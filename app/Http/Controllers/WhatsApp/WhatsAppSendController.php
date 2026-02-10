<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhatsAppSendController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Send a text message.
     */
    public function sendText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'required|string|max:4096',
            'conversation_id' => 'nullable|integer|exists:wa_conversations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $phone = WhatsAppService::normalizePhoneToE164($request->input('phone'));
        $message = $request->input('message');
        $conversationId = $request->input('conversation_id');

        try {
            $waMessage = $this->whatsAppService->sendText(
                $userId,
                $phone,
                $message,
                $conversationId
            );

            if (!$waMessage) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to send message. User may have opted out.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message_id' => $waMessage->id,
                'conversation_id' => $waMessage->wa_conversation_id,
                'status' => $waMessage->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send interactive buttons.
     */
    public function sendButtons(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|integer|exists:wa_conversations,id',
            'body_text' => 'required|string|max:1024',
            'buttons' => 'required|array|min:1|max:3',
            'buttons.*.id' => 'required|string',
            'buttons.*.title' => 'required|string|max:20',
            'header_text' => 'nullable|string|max:60',
            'footer_text' => 'nullable|string|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $conversationId = $request->input('conversation_id');
        $bodyText = $request->input('body_text');
        $buttons = $request->input('buttons');
        $headerText = $request->input('header_text');
        $footerText = $request->input('footer_text');

        try {
            $waMessage = $this->whatsAppService->sendButtons(
                $userId,
                $conversationId,
                $bodyText,
                $buttons,
                $headerText,
                $footerText
            );

            if (!$waMessage) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to send message.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message_id' => $waMessage->id,
                'conversation_id' => $waMessage->wa_conversation_id,
                'status' => $waMessage->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send template message.
     */
    public function sendTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'template' => 'required|string',
            'variables' => 'required|array',
            'conversation_id' => 'nullable|integer|exists:wa_conversations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $phone = WhatsAppService::normalizePhoneToE164($request->input('phone'));
        $template = $request->input('template');
        $variables = $request->input('variables');
        $conversationId = $request->input('conversation_id');

        try {
            $waMessage = $this->whatsAppService->sendTemplateMessage(
                $userId,
                $phone,
                $template,
                $variables,
                $conversationId
            );

            if (!$waMessage) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to send message. User may have opted out.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message_id' => $waMessage->id,
                'conversation_id' => $waMessage->wa_conversation_id,
                'status' => $waMessage->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get conversation messages.
     */
    public function getMessages(Request $request, int $conversationId)
    {
        $userId = auth()->id();

        $conversation = \App\Models\WhatsApp\WaConversation::where('id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $messages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'contact' => [
                'id' => $conversation->contact->id,
                'name' => $conversation->contact->name,
                'phone' => $conversation->contact->phone_e164,
            ],
            'state' => $conversation->state,
            'status' => $conversation->status,
            'messages' => $messages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'direction' => $msg->direction,
                    'body' => $msg->body,
                    'message_type' => $msg->message_type,
                    'status' => $msg->status,
                    'created_at' => $msg->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Update conversation status.
     */
    public function updateStatus(Request $request, int $conversationId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:open,paused,handoff,closed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $status = $request->input('status');

        $conversation = \App\Models\WhatsApp\WaConversation::where('id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $conversation->update(['status' => $status]);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'status' => $conversation->status,
        ]);
    }
}
