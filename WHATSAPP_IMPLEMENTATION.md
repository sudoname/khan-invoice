# WhatsApp Sales Assistant Implementation

## Overview

This document provides a complete summary of the production-ready WhatsApp Sales Assistant module that has been implemented for Khan Invoice (Kinvoice.ng). The module enables businesses to interact with customers via WhatsApp, create invoices conversationally, track payments, and send automated follow-ups.

## Implementation Summary

### ✅ Completed Components

#### 1. Database Layer (9 Migrations)
- `wa_accounts` - WhatsApp Business Account credentials per user
- `wa_contacts` - WhatsApp contacts with E.164 phone normalization
- `wa_conversations` - Conversation state management with JSON context
- `wa_messages` - Inbound/outbound messages with status tracking
- `leads` - Lead management with scoring and stages
- `automation_rules` - Configurable follow-up rules
- `automation_logs` - Audit trail for all automation actions
- `wa_opt_outs` - GDPR-compliant opt-out tracking
- Invoice table enhancements - Added WhatsApp relationship fields

#### 2. Models (8 Files)
- `WaAccount` - Account management with encrypted tokens
- `WaContact` - Contact management with last-seen tracking
- `WaConversation` - State machine implementation
- `WaMessage` - Message lifecycle management
- `Lead` - Lead scoring and stage progression
- `AutomationRule` - Rule configuration and message templating
- `AutomationLog` - Static logging methods
- `WaOptOut` - Opt-out/opt-in management

#### 3. Service Layer (6 Files)
- `WhatsAppClientInterface` - Provider abstraction interface
- `MetaWhatsAppClient` - Meta WhatsApp Cloud API implementation
- `WhatsAppService` - Main facade with webhook parsing
- `ConversationStateManager` - State machine with transitions
- `WhatsAppAiOrchestrator` - AI integration with action-contract pattern
- `ActionExecutor` - Deterministic action execution

#### 4. Controllers & Routes (4 Files)
- `WhatsAppWebhookController` - Webhook verification and message receiving
- `WhatsAppSendController` - Internal send endpoints
- `WhatsAppAiController` - AI testing and debugging endpoints
- `routes/whatsapp.php` - All WhatsApp routes

#### 5. Background Jobs (3 Files)
- `ProcessInboundWhatsAppMessageJob` - AI processing pipeline
- `SendWhatsAppMessageJob` - Queue-based message sending
- `SendWhatsAppFollowupJob` - Automated follow-up delivery

#### 6. Artisan Commands (1 File)
- `RunWhatsAppFollowupsCommand` - Cron job for scheduled follow-ups

#### 7. Filament Resources (4 Resources + Pages)
- `WaConversationResource` - Inbox view with chat interface
- `LeadResource` - Lead management dashboard
- `AutomationRuleResource` - Follow-up rule configuration
- `WhatsAppSettings` - Account configuration page

#### 8. Tests (3 Files)
- `WhatsAppWebhookTest` - Webhook verification and idempotency
- `MessageProcessingTest` - Invoice creation flow
- `FollowupSchedulerTest` - Automated reminders

## Architecture

### Key Design Patterns

1. **State Machine Pattern**
   - Conversations follow defined states: idle → collecting_lead → collecting_invoice → invoice_sent → awaiting_payment
   - State transitions are validated to prevent invalid flows
   - Context data is stored in JSON for flexibility

2. **Action-Contract Pattern**
   - AI outputs strict JSON schema
   - Backend validates and executes actions deterministically
   - AI cannot directly set prices, totals, or mark invoices as paid
   - Clear separation between AI suggestions and business logic

3. **Provider Abstraction**
   - Interface-based design allows multiple WhatsApp providers
   - Currently implements Meta WhatsApp Cloud API
   - Easy to add Termii, Twilio, or custom providers

4. **Queue-Based Processing**
   - All outbound messages are queued
   - Retry logic with exponential backoff
   - Prevents rate limiting issues

5. **Multi-Tenancy**
   - All data scoped by `user_id`
   - Filament resources automatically filter by authenticated user
   - No cross-tenant data leakage

## Configuration

### Environment Variables

Add to your `.env` file:

```env
# WhatsApp Configuration
WHATSAPP_PROVIDER=meta
WHATSAPP_META_BASE_URL=https://graph.facebook.com/v19.0
WHATSAPP_META_APP_SECRET=your_meta_app_secret
WHATSAPP_VERIFY_TOKEN=your_custom_verify_token
WHATSAPP_ACCESS_TOKEN=your_default_access_token

# AI Configuration
WA_AI_ENABLED=true
WA_AI_PROVIDER=openai
WA_AI_MODEL=gpt-4-turbo-preview
WA_AI_API_KEY=your_openai_api_key
```

### Config File

The main configuration is in `config/whatsapp.php`:

```php
return [
    'provider' => env('WHATSAPP_PROVIDER', 'meta'),

    'meta' => [
        'base_url' => env('WHATSAPP_META_BASE_URL', 'https://graph.facebook.com/v19.0'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'app_secret' => env('WHATSAPP_META_APP_SECRET'),
    ],

    'ai' => [
        'enabled' => env('WA_AI_ENABLED', true),
        'provider' => env('WA_AI_PROVIDER', 'openai'),
        'model' => env('WA_AI_MODEL', 'gpt-4-turbo-preview'),
        'api_key' => env('WA_AI_API_KEY'),
    ],

    'followups' => [
        'default_schedule' => [60, 1440, 4320], // 1hr, 24hr, 3days
    ],
];
```

## Setup Instructions

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Configure Queue Worker

Ensure your queue worker is running:

```bash
php artisan queue:work --tries=3 --timeout=120
```

Or use Supervisor for production:

```ini
[program:khan-invoice-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/khan-invoice/artisan queue:work --sleep=3 --tries=3 --timeout=120
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/khan-invoice/storage/logs/worker.log
```

### 3. Schedule Cron Job

Add to your crontab or Laravel scheduler:

```bash
# Run follow-ups every 15 minutes
*/15 * * * * cd /path/to/khan-invoice && php artisan whatsapp:run-followups
```

Or in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('whatsapp:run-followups')
        ->everyFifteenMinutes()
        ->withoutOverlapping();
}
```

### 4. Configure Meta WhatsApp Business

1. **Create Meta App**: Go to https://developers.facebook.com/apps
2. **Add WhatsApp Product**: Add WhatsApp Business API to your app
3. **Get Phone Number ID**: From WhatsApp → API Setup
4. **Generate Access Token**: Create permanent access token
5. **Configure Webhook**:
   - Callback URL: `https://yourdomain.com/api/webhooks/whatsapp`
   - Verify Token: `super-secret-token` (or your custom value from `.env`)
   - Subscribe to: `messages`

### 5. Configure in Filament

1. Navigate to **WhatsApp → WhatsApp Settings**
2. Enter your credentials:
   - Phone Number ID
   - WABA ID
   - Access Token
   - Verify Token
3. Click **Save Settings**
4. Click **Test Connection** to verify

### 6. Create Automation Rules

1. Navigate to **WhatsApp → Automation Rules**
2. Click **Create**
3. Configure:
   - Rule Name: "Unpaid Invoice Reminder"
   - Type: Unpaid Invoice Follow-up
   - Schedule: 60, 1440, 4320 (minutes)
   - Message Template: Use variables like `{{invoice_number}}`, `{{amount}}`
4. **Activate** the rule

## Usage

### Receiving Messages

When a customer sends a message to your WhatsApp Business number:

1. **Webhook receives message** → `WhatsAppWebhookController::receive()`
2. **Contact and conversation created** (if new)
3. **Message stored** with idempotency check
4. **Job queued** → `ProcessInboundWhatsAppMessageJob`
5. **AI processes message** → `WhatsAppAiOrchestrator`
6. **Actions executed** → `ActionExecutor`
7. **Response sent** back to customer

### Conversation Flow

```
Customer: "Hi, I need an invoice"
Bot: "Sure! What's your name?"

Customer: "John Doe"
Bot: "Thanks John! What items would you like to order?"

Customer: "2 laptops and 3 mice"
Bot: "Got it! I've created a draft invoice. Let me get the pricing..."

[Human agent sets prices in Filament]

Bot: "Your invoice is ready! Total: NGN 150,000. Pay here: https://..."
```

### Manual Sending

Use the API endpoints to send messages:

```bash
POST /api/whatsapp/send/text
{
  "phone": "+2348012345678",
  "message": "Hello from Khan Invoice!"
}
```

### Testing AI

```bash
POST /api/whatsapp/ai/test-process
{
  "conversation_id": 1,
  "message": "I need an invoice for 5 laptops"
}
```

## Available Actions

The AI can output these actions:

1. **send_message** - Send text to customer
2. **collect_field** - Store data in conversation context
3. **transition_state** - Change conversation state
4. **create_invoice** - Create draft invoice (prices set to 0)
5. **send_invoice** - Send invoice link to customer
6. **handoff** - Escalate to human agent

## Security Features

### 1. Webhook Signature Verification

All webhooks are verified using HMAC SHA256:

```php
public function verifyWebhookSignature(string $payload, string $signature, string $appSecret): bool
{
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
    return hash_equals($expectedSignature, $signature);
}
```

### 2. Encrypted Tokens

Access tokens and verify tokens are encrypted in the database:

```php
protected $casts = [
    'access_token' => 'encrypted',
    'verify_token' => 'encrypted',
];
```

### 3. Idempotency

Messages are deduplicated using unique `provider_message_id`:

```php
$existingMessage = WaMessage::where('provider_message_id', $providerMessageId)->first();
if ($existingMessage) {
    return; // Skip duplicate
}
```

### 4. Opt-Out Compliance

Customers can opt-out at any time:

```
Customer: "STOP"
Bot: "You have been unsubscribed. Reply START to opt back in."
```

### 5. Rate Limiting

API calls include retry logic for 429/5xx errors:

```php
Http::withToken($account->access_token)
    ->retry(3, 1000, function ($exception) {
        return in_array($exception->response->status(), [429, 500, 502, 503, 504]);
    })
    ->post($url, $payload);
```

## Monitoring & Logging

### Logs

All actions are logged to:
- `storage/logs/laravel.log` - Application logs
- `automation_logs` table - Audit trail

### Important Log Entries

```php
Log::info('WhatsApp message sent', ['message_id' => $id]);
Log::error('WhatsApp API error', ['status' => 400, 'error' => 'Invalid phone']);
Log::warning('Message not sent - user opted out', ['phone' => $phone]);
```

### Monitoring Queries

```sql
-- Check recent messages
SELECT * FROM wa_messages ORDER BY created_at DESC LIMIT 20;

-- Check failed messages
SELECT * FROM wa_messages WHERE status = 'failed';

-- Check conversations needing handoff
SELECT * FROM wa_conversations WHERE human_handoff = 1 AND status = 'open';

-- Check automation log failures
SELECT * FROM automation_logs WHERE status = 'failed' ORDER BY created_at DESC;
```

## Troubleshooting

### Issue: Webhooks Not Received

1. Check webhook URL is publicly accessible
2. Verify SSL certificate is valid
3. Check webhook configuration in Meta dashboard
4. Review `storage/logs/laravel.log` for errors

### Issue: Messages Not Sending

1. Check queue worker is running: `php artisan queue:work`
2. Verify WhatsApp account status in database
3. Check access token hasn't expired
4. Review failed jobs: `php artisan queue:failed`

### Issue: AI Not Responding

1. Verify AI is enabled: `config('whatsapp.ai.enabled')`
2. Check OpenAI API key is valid
3. Review rate limits on OpenAI account
4. Check `automation_logs` for AI errors

### Issue: Follow-ups Not Sending

1. Verify cron job is running
2. Check automation rules are active
3. Verify invoices have WhatsApp contacts
4. Review schedule configuration

## Testing

Run the test suite:

```bash
# All tests
php artisan test

# WhatsApp tests only
php artisan test --filter WhatsApp

# Specific test
php artisan test tests/Feature/WhatsApp/WhatsAppWebhookTest.php
```

## Performance Considerations

### Database Indexes

All important queries have indexes:
- `wa_contacts(user_id, phone_e164)` - UNIQUE
- `wa_messages(provider_message_id)` - UNIQUE
- `wa_conversations(user_id, status, state)`
- `invoices(wa_contact_id, wa_conversation_id)`

### Optimization Tips

1. **Enable queue batching** for high-volume scenarios
2. **Use Redis** for queue driver in production
3. **Enable database query caching** for conversation context
4. **Monitor API rate limits** and implement circuit breakers if needed
5. **Archive old conversations** (status='closed', updated > 90 days ago)

## Future Enhancements

Consider implementing:

1. **Rich Media Support** - Images, documents, voice messages
2. **Payment Integration** - Direct payment via WhatsApp
3. **Broadcast Messages** - Send to multiple customers
4. **AI Training** - Fine-tune on your business data
5. **Analytics Dashboard** - Conversation metrics, conversion rates
6. **Multi-language Support** - Detect and respond in customer's language
7. **CRM Integration** - Sync with external CRM systems

## API Reference

### Internal Endpoints

All endpoints require authentication via `auth:sanctum`.

#### Send Text Message

```
POST /api/whatsapp/send/text
Content-Type: application/json

{
  "phone": "+2348012345678",
  "message": "Hello!",
  "conversation_id": 1 // optional
}
```

#### Send Buttons

```
POST /api/whatsapp/send/buttons
Content-Type: application/json

{
  "conversation_id": 1,
  "body_text": "Choose an option:",
  "buttons": [
    {"id": "option1", "title": "Yes"},
    {"id": "option2", "title": "No"}
  ],
  "header_text": "Confirm Order", // optional
  "footer_text": "Reply anytime" // optional
}
```

#### Get Conversation Messages

```
GET /api/whatsapp/conversations/{conversationId}/messages
```

#### Update Conversation Status

```
PATCH /api/whatsapp/conversations/{conversationId}/status
Content-Type: application/json

{
  "status": "closed" // open|paused|handoff|closed
}
```

## File Structure

```
app/
├── Console/Commands/
│   └── RunWhatsAppFollowupsCommand.php
├── Filament/
│   ├── Pages/
│   │   └── WhatsAppSettings.php
│   └── Resources/
│       ├── AutomationRuleResource.php
│       ├── LeadResource.php
│       └── WaConversationResource.php
├── Http/Controllers/WhatsApp/
│   ├── WhatsAppAiController.php
│   ├── WhatsAppSendController.php
│   └── WhatsAppWebhookController.php
├── Jobs/WhatsApp/
│   ├── ProcessInboundWhatsAppMessageJob.php
│   ├── SendWhatsAppFollowupJob.php
│   └── SendWhatsAppMessageJob.php
├── Models/WhatsApp/
│   ├── AutomationLog.php
│   ├── AutomationRule.php
│   ├── Lead.php
│   ├── WaAccount.php
│   ├── WaContact.php
│   ├── WaConversation.php
│   ├── WaMessage.php
│   └── WaOptOut.php
└── Services/WhatsApp/
    ├── ActionExecutor.php
    ├── ConversationStateManager.php
    ├── Contracts/
    │   └── WhatsAppClientInterface.php
    ├── MetaWhatsAppClient.php
    ├── WhatsAppAiOrchestrator.php
    └── WhatsAppService.php

config/
└── whatsapp.php

database/migrations/
├── 2026_02_09_000001_create_wa_accounts_table.php
├── 2026_02_09_000002_create_wa_contacts_table.php
├── 2026_02_09_000003_create_wa_conversations_table.php
├── 2026_02_09_000004_create_wa_messages_table.php
├── 2026_02_09_000005_create_leads_table.php
├── 2026_02_09_000006_create_automation_rules_table.php
├── 2026_02_09_000007_create_automation_logs_table.php
├── 2026_02_09_000008_create_wa_opt_outs_table.php
└── 2026_02_09_000009_add_whatsapp_fields_to_invoices_table.php

routes/
└── whatsapp.php

tests/Feature/WhatsApp/
├── FollowupSchedulerTest.php
├── MessageProcessingTest.php
└── WhatsAppWebhookTest.php
```

## Support & Maintenance

### Regular Maintenance Tasks

1. **Weekly**: Review failed messages and automation logs
2. **Monthly**: Archive old closed conversations
3. **Quarterly**: Review and optimize automation rules
4. **As needed**: Update AI prompts based on conversation quality

### Getting Help

- Check logs first: `storage/logs/laravel.log`
- Review automation logs: Query `automation_logs` table
- Test webhook: Use Meta's "Test" button in webhook configuration
- Test AI: Use `/api/whatsapp/ai/test-process` endpoint

## Credits

- **Laravel Framework**: https://laravel.com
- **Filament Admin**: https://filamentphp.com
- **Meta WhatsApp Cloud API**: https://developers.facebook.com/docs/whatsapp/cloud-api
- **OpenAI GPT-4**: https://openai.com

---

**Implementation Date**: February 9, 2026
**Version**: 1.0.0
**Status**: Production Ready ✅

All components have been implemented, tested, and documented. The system is ready for deployment to your local environment for testing, and subsequently to production.
