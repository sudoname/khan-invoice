# Khan Invoice - Weeks 6-10 Implementation Plan

## Week 6: WhatsApp Business Integration ⏳ (90% Complete)

### Status: In Progress

### Completed:
- ✅ WhatsApp logs migration
- ✅ Notification preferences migration (WhatsApp fields)
- ✅ WhatsAppLog model
- ✅ WhatsAppService (Twilio integration)
- ✅ WhatsAppChannel notification channel
- ✅ Updated NotificationPreference model
- ✅ Updated User model
- ✅ Twilio configuration

### Remaining:
- ⏳ Update 4 notification classes (add `toWhatsApp()` method)
- ⏳ Update Filament NotificationSettings page (add WhatsApp section)
- ⏳ Run migrations
- ⏳ Create test suite (test-week6.php)

### Files Created (Week 6):
1. `database/migrations/..._create_whatsapp_logs_table.php`
2. `database/migrations/..._add_whatsapp_to_notification_preferences.php`
3. `app/Models/WhatsAppLog.php`
4. `app/Services/WhatsAppService.php`
5. `app/Notifications/Channels/WhatsAppChannel.php`

### Files Updated (Week 6):
1. `app/Models/NotificationPreference.php` - Added WhatsApp fields & methods
2. `app/Models/User.php` - Added `whatsAppLogs()` relationship
3. `config/services.php` - Added Twilio configuration
4. `.env.example` - Added Twilio environment variables

### Configuration Required:
```env
# Add to .env
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_WHATSAPP_FROM=+14155238886
```

### Quick Completion Steps:
```bash
# 1. Add toWhatsApp() to notifications (4 files)
# 2. Update Filament UI
# 3. Run migrations
php artisan migrate

# 4. Test
php test-week6.php
```

---

## Week 7: Recurring Invoices (PLANNED)

### Goal: Automatically generate invoices on a schedule

### Implementation Plan:

**Database:**
- `recurring_invoice_templates` table
  - user_id, customer_id, business_profile_id
  - frequency (daily, weekly, monthly, quarterly, yearly)
  - start_date, end_date, next_invoice_date
  - status (active, paused, completed)
  - invoice template data (items, amounts, etc.)

**Models:**
- `RecurringInvoiceTemplate` model

**Commands:**
- `app/Console/Commands/GenerateRecurringInvoices.php`
  - Runs daily, checks templates where next_invoice_date <= today
  - Generates invoice from template
  - Updates next_invoice_date
  - Sends InvoiceSentNotification

**Filament UI:**
- `RecurringInvoiceTemplateResource` - CRUD for templates
- Calendar view for scheduled invoices

**Scheduler:**
```php
$schedule->command('invoices:generate-recurring')->daily();
```

---

## Week 8: Customer Portal (PLANNED)

### Goal: Customers can view invoices and make payments

### Implementation Plan:

**Routes:**
```php
Route::prefix('portal/{public_id}')->group(function () {
    Route::get('/', [CustomerPortalController::class, 'show']);
    Route::get('/invoice/{invoice}', [CustomerPortalController::class, 'invoice']);
    Route::post('/payment/initiate', [CustomerPortalController::class, 'initiatePayment']);
    Route::get('/download/{invoice}', [CustomerPortalController::class, 'downloadPdf']);
});
```

**Controllers:**
- `CustomerPortalController`

**Views:**
- `resources/views/customer-portal/dashboard.blade.php`
- `resources/views/customer-portal/invoice.blade.php`

**Features:**
- View all invoices (paid, unpaid, overdue)
- Download PDF invoices
- Make payments via Paystack
- Update customer profile
- View payment history
- No authentication required (public_id based)

---

## Week 9: Team Collaboration (PLANNED)

### Goal: Multiple users can collaborate on invoices

### Implementation Plan:

**Database:**
- `team_members` table (pivot)
  - user_id, business_profile_id, role
  - permissions (JSON: can_create_invoice, can_edit, can_delete, can_view_reports)
- `invoice_comments` table
  - invoice_id, user_id, comment, created_at
- `activity_logs` table
  - user_id, subject_type, subject_id, action, description

**Models:**
- `TeamMember` model
- `InvoiceComment` model
- `ActivityLog` model

**Middleware:**
- `CheckTeamPermission` - Verify user has permission

**Filament Resources:**
- `TeamMemberResource` - Manage team members
- Add comments section to InvoiceResource

**Features:**
- Invite team members by email
- Assign roles (Admin, Manager, Staff)
- Granular permissions
- Comment on invoices
- Activity log (who created/edited what)
- @mention team members

---

## Week 10: Quotes & Estimates (PLANNED)

### Goal: Create quotes that can be converted to invoices

### Implementation Plan:

**Database:**
- `quotes` table (similar to invoices)
  - All invoice fields
  - quote_number, expiry_date
  - status (draft, sent, accepted, rejected, expired, converted)
  - version_number
  - parent_quote_id (for revisions)

**Models:**
- `Quote` model (very similar to Invoice)
- `QuoteItem` model

**Filament Resource:**
- `QuoteResource` - Full CRUD
- Actions: Send, Accept, Reject, Convert to Invoice, Duplicate, Create Revision

**Features:**
- Quote numbering (QUO-2025-00000001)
- Expiration dates with automatic status updates
- Customer can accept/reject via portal
- Convert quote → invoice (one click)
- Quote versions/revisions
- Quote templates

**Scheduler:**
```php
$schedule->command('quotes:check-expired')->daily();
```

---

## Implementation Priority

### Recommended Order:
1. ✅ **Weeks 1-4** (Complete)
2. ⏳ **Week 6: WhatsApp** (90% done - finish this first!)
3. **Week 7: Recurring Invoices** (High value, medium complexity)
4. **Week 10: Quotes** (High value, similar to invoices)
5. **Week 8: Customer Portal** (Good UX improvement)
6. **Week 9: Team Collaboration** (Complex, for larger businesses)

### Alternative Order (if time-constrained):
Focus on highest business value:
1. Week 6: WhatsApp (finish it!)
2. Week 7: Recurring Invoices
3. Week 10: Quotes & Estimates
4. Skip or postpone Week 8 & 9

---

## Estimated Completion Time

**Week 6** (WhatsApp): 2-3 hours remaining
**Week 7** (Recurring): 8-10 hours
**Week 8** (Customer Portal): 10-12 hours
**Week 9** (Team Collaboration): 12-15 hours
**Week 10** (Quotes): 8-10 hours

**Total**: 40-50 hours for all 5 features

---

## Testing Strategy

Each week should have:
1. **Migration tests** - Schema validation
2. **Model tests** - Relationships, methods
3. **Feature tests** - End-to-end workflows
4. **Manual testing** - UI interactions

---

## Deployment Considerations

### For Production:

1. **WhatsApp**: Requires Twilio account ($20/month minimum)
2. **Recurring Invoices**: Ensure cron job runs daily
3. **Customer Portal**: Add to domain (portal.kinvoice.ng)
4. **Team Collaboration**: Requires email service for invitations
5. **Quotes**: Similar to invoices, minimal infrastructure

---

## Next Steps

To continue implementation:

1. **Finish Week 6**:
   - Update 4 notification files
   - Update Filament UI
   - Run migrations
   - Test

2. **Choose next feature** based on priority
3. **Implement systematically** (database → models → controllers → UI → tests)
4. **Test locally** before deployment
5. **Deploy to staging** for real-world testing

---

## Questions to Consider

1. **WhatsApp**: Do you have a Twilio account? (Free trial available)
2. **Recurring Invoices**: What frequencies do you need? (monthly is most common)
3. **Customer Portal**: Should customers need to create accounts?
4. **Team**: How many team members per business?
5. **Quotes**: Do you need quote approval workflow?

Let me know which feature to prioritize next!
