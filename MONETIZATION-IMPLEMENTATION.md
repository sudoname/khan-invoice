# Khan Invoice - Monetization System Implementation

## Overview
This document tracks the implementation of the subscription-based monetization system for Khan Invoice. The system uses Paystack for payment processing and provides tiered subscription plans with usage tracking.

---

## ✅ Phase 1: Database Foundation (COMPLETED)

### Migrations Created:
1. **`plans` table** - Subscription plan configurations
2. **`subscriptions` table** - User subscription records
3. **`usage_records` table** - Track usage (invoices, SMS, WhatsApp, API)
4. **`payment_transactions` table** - Payment history and receipts

### Schema Details:

#### Plans Table
- Plan metadata (name, slug, description)
- Pricing (monthly, yearly, currency)
- Usage limits (invoices, customers, team members, credits)
- Feature flags (multi-currency, API access, white label, etc.)
- Status fields (is_active, is_popular, sort_order)

#### Subscriptions Table
- User and plan relationships
- Subscription status (active, canceled, expired, past_due)
- Billing cycle (monthly, yearly)
- Period dates (trial, current period, expiration)
- Paystack integration fields
- Usage counters (invoices, customers, SMS, WhatsApp, API)

#### Usage Records Table
- Granular usage tracking per user
- Types: invoice_created, sms_sent, whatsapp_sent, api_request, customer_created
- Quantity and metadata for detailed reporting

#### Payment Transactions Table
- Transaction history for billing
- Payment gateway integration (Paystack)
- Transaction status and references
- Metadata for receipts and reporting

---

## 📋 Phase 2: Models & Relationships (TODO)

### Models to Create:
```bash
php artisan make:model Plan
php artisan make:model Subscription
php artisan make:model UsageRecord
php artisan make:model PaymentTransaction
```

### Relationships:
- **User** hasOne Subscription
- **User** hasMany PaymentTransactions
- **User** hasMany UsageRecords
- **Subscription** belongsTo User
- **Subscription** belongsTo Plan
- **Subscription** hasMany PaymentTransactions
- **Subscription** hasMany UsageRecords

### Model Methods Needed:
```php
// Plan.php
public function canCreateInvoice(Subscription $sub): bool
public function canCreateCustomer(Subscription $sub): bool
public function canSendSMS(Subscription $sub): bool
public function hasFeature(string $feature): bool

// Subscription.php
public function isActive(): bool
public function isTrial(): bool
public function isExpired(): bool
public function daysUntilRenewal(): int
public function incrementUsage(string $type, int $quantity = 1): void
public function resetUsage(): void
public function hasReachedLimit(string $type): bool

// User.php (additions)
public function subscription(): HasOne
public function canCreateInvoice(): bool
public function canSendSMS(): bool
public function plan(): ?Plan
```

---

## 📋 Phase 3: Subscription Seeder (TODO)

### Create Plans Seeder:
```bash
php artisan make:seeder PlansSeeder
```

### Plans to Seed:

#### Free Plan
```php
[
    'name' => 'Free',
    'slug' => 'free',
    'price_monthly' => 0,
    'price_yearly' => 0,
    'max_invoices' => 5,
    'max_customers' => 3,
    'sms_credits_monthly' => 0,
    'whatsapp_credits_monthly' => 0,
    'api_access' => false,
    // All feature flags false
]
```

#### Starter Plan (₦5,000/month)
```php
[
    'name' => 'Starter',
    'slug' => 'starter',
    'price_monthly' => 5000,
    'price_yearly' => 50000, // 17% discount
    'max_invoices' => 50,
    'max_customers' => 25,
    'sms_credits_monthly' => 100,
    'whatsapp_credits_monthly' => 50,
    'api_access' => true,
    'api_requests_monthly' => 10000,
    'multi_currency' => true,
]
```

#### Professional Plan (₦15,000/month)
```php
[
    'name' => 'Professional',
    'slug' => 'professional',
    'price_monthly' => 15000,
    'price_yearly' => 150000,
    'max_invoices' => -1, // unlimited
    'max_customers' => -1,
    'sms_credits_monthly' => 500,
    'whatsapp_credits_monthly' => 200,
    'api_access' => true,
    'api_requests_monthly' => 100000,
    'multi_currency' => true,
    'recurring_invoices' => true,
    'remove_branding' => true,
    'advanced_reports' => true,
    'is_popular' => true,
]
```

#### Business Plan (₦35,000/month)
```php
[
    'name' => 'Business',
    'slug' => 'business',
    'price_monthly' => 35000,
    'price_yearly' => 350000,
    'max_invoices' => -1,
    'max_customers' => -1,
    'max_team_members' => 5,
    'sms_credits_monthly' => 2000,
    'whatsapp_credits_monthly' => 1000,
    'api_access' => true,
    'api_requests_monthly' => 500000,
    'storage_gb' => 50,
    'multi_currency' => true,
    'recurring_invoices' => true,
    'remove_branding' => true,
    'white_label' => true,
    'custom_domain' => true,
    'priority_support' => true,
    'advanced_reports' => true,
]
```

---

## 📋 Phase 4: Paystack Integration (TODO)

### Install Paystack Package:
```bash
composer require unicodeveloper/laravel-paystack
```

### Create Paystack Service:
```php
// app/Services/PaystackService.php
class PaystackService
{
    public function initializeSubscription(User $user, Plan $plan, string $cycle): array
    public function verifyTransaction(string $reference): array
    public function createSubscriptionPlan(Plan $plan): array
    public function cancelSubscription(string $code): array
    public function handleWebhook(array $data): void
}
```

### Environment Variables:
```env
PAYSTACK_PUBLIC_KEY=pk_test_xxx
PAYSTACK_SECRET_KEY=sk_test_xxx
PAYSTACK_PAYMENT_URL=https://api.paystack.co
```

### Webhook Endpoint:
```php
// routes/api.php
Route::post('/webhooks/paystack', [WebhookController::class, 'paystack']);
```

---

## 📋 Phase 5: Subscription Management Service (TODO)

### Create Service:
```php
// app/Services/SubscriptionService.php
class SubscriptionService
{
    public function subscribe(User $user, Plan $plan, string $cycle): Subscription
    public function upgrade(Subscription $sub, Plan $newPlan): Subscription
    public function downgrade(Subscription $sub, Plan $newPlan): Subscription
    public function cancel(Subscription $sub, bool $immediate = false): Subscription
    public function reactivate(Subscription $sub): Subscription
    public function checkAndExpireSubscriptions(): void // Scheduled command
    public function resetMonthlyUsage(): void // Scheduled command
}
```

### Scheduled Commands:
```php
// app/Console/Kernel.php
$schedule->call([SubscriptionService::class, 'checkAndExpireSubscriptions'])->daily();
$schedule->call([SubscriptionService::class, 'resetMonthlyUsage'])->monthlyOn(1, '00:00');
```

---

## 📋 Phase 6: Usage Tracking (TODO)

### Create Usage Tracker:
```php
// app/Services/UsageTracker.php
class UsageTracker
{
    public function trackInvoiceCreated(User $user): void
    public function trackCustomerCreated(User $user): void
    public function trackSmsSent(User $user): void
    public function trackWhatsAppSent(User $user): void
    public function trackApiRequest(User $user): void
    public function canPerformAction(User $user, string $action): bool
}
```

### Integration Points:
- **InvoiceResource@create** → Track invoice creation
- **CustomerResource@create** → Track customer creation
- **SmsChannel@send** → Track SMS usage
- **WhatsAppChannel@send** → Track WhatsApp usage
- **ApiRateLimit middleware** → Track API requests

---

## 📋 Phase 7: Plan Enforcement Middleware (TODO)

### Create Middleware:
```bash
php artisan make:middleware EnforceSubscriptionLimits
```

### Middleware Logic:
```php
public function handle(Request $request, Closure $next, string $action)
{
    $user = $request->user();

    if (!$user->canPerformAction($action)) {
        throw new SubscriptionLimitReachedException(
            "You've reached your plan limit for {$action}. Please upgrade."
        );
    }

    return $next($request);
}
```

### Apply Middleware:
```php
// In InvoiceResource, CustomerResource, etc.
public static function canCreate(): bool
{
    return auth()->user()->canCreateInvoice();
}
```

---

## 📋 Phase 8: Filament UI (TODO)

### Pages to Create:

#### 1. Subscription Plans Page (`/app/plans`)
- Display all available plans
- Compare features
- "Choose Plan" buttons
- Current plan badge

#### 2. Billing Page (`/app/billing`)
- Current subscription details
- Usage statistics (invoices used, SMS credits, etc.)
- Upgrade/Downgrade buttons
- Cancel subscription button

#### 3. Payment History Page (`/app/payments`)
- Table of all transactions
- Download invoices/receipts
- Payment status

#### 4. Checkout Page (`/app/checkout/{plan}`)
- Plan summary
- Billing cycle selection (monthly/yearly)
- Payment form (Paystack inline)
- Apply coupon code

### Widgets:
- **Usage Dashboard Widget** - Show current usage vs limits
- **Subscription Status Widget** - Days until renewal, upgrade CTA

---

## 📋 Phase 9: Testing (TODO)

### Test Cases:
1. User subscribes to Starter plan (monthly)
2. User creates invoices until limit reached
3. User tries to exceed limit → Error shown
4. User upgrades to Professional plan
5. Limits updated, user can continue
6. User cancels subscription → Grace period
7. Subscription expires → Downgrade to Free
8. Payment fails → Subscription marked past_due
9. Webhook receives payment confirmation
10. Usage resets on 1st of month

---

## 📋 Phase 10: Deployment Checklist (TODO)

### Pre-Deployment:
- [ ] Test Paystack webhooks on staging
- [ ] Configure Paystack production keys
- [ ] Set up SSL for payment pages
- [ ] Test all subscription flows
- [ ] Create admin panel for plan management
- [ ] Set up monitoring for failed payments

### Post-Deployment:
- [ ] Monitor first subscriptions
- [ ] Check usage tracking accuracy
- [ ] Verify webhook processing
- [ ] Test upgrade/downgrade flows
- [ ] Verify email notifications sent

---

## Pricing Strategy

### Nigerian Market:
- **Free Plan**: Acquisition tool
- **Starter (₦5,000/month)**: Freelancers, small businesses
- **Professional (₦15,000/month)**: SMEs (most popular)
- **Business (₦35,000/month)**: Agencies, larger businesses

### Revenue Projections:
- 100 free users → 0 revenue
- 20 starter users → ₦100,000/month
- 50 professional users → ₦750,000/month
- 10 business users → ₦350,000/month
- **Total**: ₦1,200,000/month (~$1,500/month)

### Growth Targets:
- Month 3: 10 paying subscribers
- Month 6: 50 paying subscribers
- Month 12: 200 paying subscribers (~₦2M/month revenue)

---

## Next Steps

**Immediate actions needed:**
1. Run migrations: `php artisan migrate`
2. Seed plans: `php artisan db:seed --class=PlansSeeder` (after creating seeder)
3. Set up Paystack test keys in `.env`
4. Continue with Phase 2 (Models & Relationships)

**Priority order:**
1. Models & seeder (foundational)
2. Paystack integration (payment processing)
3. Usage tracking (enforce limits)
4. Filament UI (user-facing subscription management)
5. Testing & deployment

---

## Files Created in Phase 1:
- `database/migrations/2025_12_13_153149_create_plans_table.php`
- `database/migrations/2025_12_13_153313_create_subscriptions_table.php`
- `database/migrations/2025_12_13_153315_create_usage_records_table.php`
- `database/migrations/2025_12_13_153317_create_payment_transactions_table.php`

## Implementation Status:
✅ Phase 1: Database Foundation (100%)
⏳ Phase 2-10: In Progress (0%)

---

**Total Estimated Time**: 2-3 weeks for full implementation
**Phase 1 Completed**: December 13, 2025
