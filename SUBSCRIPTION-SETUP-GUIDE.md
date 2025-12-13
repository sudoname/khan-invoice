# Khan Invoice - Subscription System Setup Guide

## Quick Start

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Subscription Plans
```bash
php artisan db:seed --class=PlansSeeder
```

Creates 4 tiers: Free, Starter (¦5k), Professional (¦15k), Business (¦35k)

### 3. Configure Paystack
Add to `.env`:
```env
PAYSTACK_PUBLIC_KEY=pk_test_xxx
PAYSTACK_SECRET_KEY=sk_test_xxx
```

### 4. Set Up Scheduler
Add to crontab:
```bash
* * * * * cd /path/to/khan-invoice && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Start Queue Worker
```bash
php artisan queue:work --tries=3
```

---

## Testing

### Assign Free Plan to User
```php
php artisan tinker

$user = App\Models\User::find(1);
$service = app(App\Services\SubscriptionService::class);
$freePlan = App\Models\Plan::where('slug', 'free')->first();
$service->subscribe($user, $freePlan, 'monthly');
```

### Track Usage
```php
$tracker = app(App\Services\UsageTracker::class);
$tracker->trackInvoiceCreated($user);
$summary = $tracker->getUsageSummary($user);
```

### Test Scheduled Commands
```bash
php artisan subscriptions:check-expired
php artisan subscriptions:reset-usage
```

---

## Production Setup

### 1. Switch to Live Keys
```env
PAYSTACK_PUBLIC_KEY=pk_live_xxx
PAYSTACK_SECRET_KEY=sk_live_xxx
```

### 2. Configure Webhook
Paystack Dashboard ’ Settings ’ Webhooks:
- URL: `https://kinvoice.ng/api/webhooks/paystack`
- Events: charge.success, subscription.create, subscription.disable, invoice.payment_failed

### 3. Test Webhook
Use Paystack webhook tester to verify signature validation.

---

## Available Pages

- **Subscription Plans**: `/app/subscription-plans`
- **My Subscription**: `/app/my-subscription`
- **Payment History**: `/app/payment-history`

---

## Key Features

 4-tier subscription plans
 Monthly/yearly billing cycles
 Usage tracking (invoices, SMS, WhatsApp, API)
 Automatic limit enforcement
 Paystack integration
 Webhook handling
 Beautiful Filament UI
 Admin bypass

---

## Monitoring

### Check Active Subscriptions
```php
App\Models\Subscription::active()->count();
```

### Calculate MRR
```php
App\Models\Subscription::active()
    ->where('billing_cycle', 'monthly')
    ->sum('amount');
```

### This Month Revenue
```php
App\Models\PaymentTransaction::successful()
    ->whereMonth('created_at', now()->month)
    ->sum('amount');
```

---

## Troubleshooting

### Plans Not Showing
```bash
php artisan db:seed --class=PlansSeeder
php artisan cache:clear
```

### Webhook Not Working
- Check webhook URL in Paystack dashboard
- Verify signature validation
- Check logs: `tail -f storage/logs/laravel.log`

### Limits Not Enforced
- Verify middleware: `subscription.limit` in bootstrap/app.php
- Check user has subscription: `$user->hasActiveSubscription()`

---

## Next Steps

1. Configure production Paystack keys
2. Test payment flow end-to-end
3. Set up monitoring (Telescope, Sentry)
4. Configure email notifications
5. Optimize performance (caching, indexes)

---

Last Updated: December 2025
