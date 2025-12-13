<?php

namespace App\Http\Middleware;

use App\Services\UsageTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Notifications\Notification;

class EnforceSubscriptionLimits
{
    public function __construct(
        private UsageTracker $usageTracker
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $action  The action to check (create_invoice, create_customer, send_sms, etc.)
     */
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $user = $request->user();

        // If no user is authenticated, let the request through (auth middleware will handle)
        if (!$user) {
            return $next($request);
        }

        // Admin users bypass all limits
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Check if user can perform the action
        if (!$this->usageTracker->canPerformAction($user, $action)) {
            return $this->handleLimitReached($request, $user, $action);
        }

        return $next($request);
    }

    /**
     * Handle when a limit is reached
     */
    private function handleLimitReached(Request $request, $user, string $action): Response
    {
        $subscription = $user->subscription;
        $plan = $subscription?->plan;

        $actionMessages = [
            'create_invoice' => [
                'title' => 'Invoice Limit Reached',
                'message' => 'You have reached your plan limit for invoices. Please upgrade to create more invoices.',
            ],
            'create_customer' => [
                'title' => 'Customer Limit Reached',
                'message' => 'You have reached your plan limit for customers. Please upgrade to add more customers.',
            ],
            'send_sms' => [
                'title' => 'SMS Credits Depleted',
                'message' => 'You have used all your SMS credits for this period. Please upgrade your plan or purchase additional credits.',
            ],
            'send_whatsapp' => [
                'title' => 'WhatsApp Credits Depleted',
                'message' => 'You have used all your WhatsApp credits for this period. Please upgrade your plan.',
            ],
            'make_api_request' => [
                'title' => 'API Request Limit Reached',
                'message' => 'You have reached your API request limit for this period. Please upgrade to increase your limit.',
            ],
        ];

        $messageData = $actionMessages[$action] ?? [
            'title' => 'Plan Limit Reached',
            'message' => 'You have reached your plan limit. Please upgrade to continue.',
        ];

        // For Filament requests (web interface)
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $messageData['message'],
                'limit_reached' => true,
                'current_plan' => $plan?->name,
                'upgrade_url' => route('filament.app.pages.subscription-plans'),
            ], 403);
        }

        // For web requests, show Filament notification and redirect
        Notification::make()
            ->title($messageData['title'])
            ->body($messageData['message'])
            ->warning()
            ->actions([
                \Filament\Notifications\Actions\Action::make('upgrade')
                    ->label('Upgrade Plan')
                    ->url(route('filament.app.pages.subscription-plans'))
                    ->button(),
            ])
            ->persistent()
            ->send();

        return redirect()->back();
    }
}
