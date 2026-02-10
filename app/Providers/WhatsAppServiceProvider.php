<?php

namespace App\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppClientInterface;
use App\Services\WhatsApp\MetaWhatsAppClient;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the WhatsApp client interface to the appropriate implementation
        $this->app->bind(WhatsAppClientInterface::class, function ($app) {
            $provider = config('whatsapp.provider', 'meta');

            return match ($provider) {
                'meta' => new MetaWhatsAppClient(),
                // Add other providers here as needed
                // 'termii' => new TermiiWhatsAppClient(),
                // 'twilio' => new TwilioWhatsAppClient(),
                default => new MetaWhatsAppClient(),
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
