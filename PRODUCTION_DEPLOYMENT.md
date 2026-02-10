# WhatsApp Sales Assistant - Production Deployment Checklist

## ✅ Completed (Local)

- [x] All 9 migrations ran successfully
- [x] Database tables created: `wa_accounts`, `wa_contacts`, `wa_conversations`, `wa_messages`, `leads`, `automation_rules`, `automation_logs`, `wa_opt_outs`
- [x] Invoice table enhanced with WhatsApp fields
- [x] All routes registered at `/api/webhooks/whatsapp`
- [x] Queue worker tested and functional
- [x] Code pushed to GitHub: `feature/payment-orchestration-v2`

**Commits:**
- `4ee5491` - Add production-ready WhatsApp Sales Assistant module
- `23abdda` - Fix MySQL index name length issue

---

## 🚀 Production Deployment Steps

### 1. Pull Latest Code on Production Server

```bash
# SSH into production server
ssh your-production-server

# Navigate to project directory
cd /path/to/khan-invoice

# Pull latest changes
git fetch origin
git checkout feature/payment-orchestration-v2
git pull origin feature/payment-orchestration-v2
```

### 2. Update Environment Variables

Add to production `.env`:

```env
# WhatsApp Business API (Meta Cloud API)
WHATSAPP_PROVIDER=meta
WHATSAPP_META_BASE_URL=https://graph.facebook.com/v19.0
WHATSAPP_VERIFY_TOKEN=super-secret-token
WHATSAPP_ACCESS_TOKEN=your_permanent_access_token_here
WHATSAPP_META_APP_SECRET=your_meta_app_secret_here

# WhatsApp AI Configuration
WA_AI_ENABLED=true
WA_AI_PROVIDER=openai
WA_AI_MODEL=gpt-4-turbo-preview
WA_AI_API_KEY=your_openai_api_key_here
```

**Important Notes:**
- Use a **permanent access token** from Meta (not temporary)
- Keep `WHATSAPP_VERIFY_TOKEN` secret and unique
- Get `WHATSAPP_META_APP_SECRET` from Meta App Dashboard → Settings → Basic

### 3. Run Database Migrations

```bash
# Backup database first!
php artisan backup:database  # or your backup method

# Run migrations
php artisan migrate --force

# Verify migrations
php artisan migrate:status | grep "wa_"
```

**Expected Output:**
```
✓ 2026_02_09_000001_create_wa_accounts_table - Ran
✓ 2026_02_09_000002_create_wa_contacts_table - Ran
✓ 2026_02_09_000003_create_wa_conversations_table - Ran
✓ 2026_02_09_000004_create_wa_messages_table - Ran
✓ 2026_02_09_000005_create_leads_table - Ran
✓ 2026_02_09_000006_create_automation_rules_table - Ran
✓ 2026_02_09_000007_create_automation_logs_table - Ran
✓ 2026_02_09_000008_create_wa_opt_outs_table - Ran
✓ 2026_02_09_000009_add_whatsapp_fields_to_invoices_table - Ran
```

### 4. Clear All Caches

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan optimize
```

### 5. Restart Queue Workers

```bash
# If using Supervisor
sudo supervisorctl restart khan-invoice-worker

# If using systemd
sudo systemctl restart khan-invoice-queue

# Or manually restart
php artisan queue:restart
```

**Verify Queue is Running:**
```bash
# Check queue worker status
php artisan queue:work --once

# Monitor queue in real-time
php artisan queue:listen
```

### 6. Configure Meta WhatsApp Webhook

#### Step 1: Go to Meta Business Manager
1. Navigate to https://developers.facebook.com/apps
2. Select your WhatsApp app
3. Go to **WhatsApp → Configuration**

#### Step 2: Configure Webhook
Click "Edit" under Webhook section:

- **Callback URL**: `https://kinvoice.ng/api/webhooks/whatsapp`
- **Verify Token**: `super-secret-token` (must match `.env`)
- **Webhook Fields**: Check `messages`

#### Step 3: Test Webhook Verification
Meta will send a GET request to verify the endpoint. You should see:
- ✅ "Webhook verified successfully" in your Laravel logs
- ✅ Green checkmark in Meta dashboard

#### Step 4: Subscribe to Messages
Click "Subscribe" to start receiving messages.

### 7. Configure Cron Job for Automated Follow-ups

Add to production crontab:

```bash
# Edit crontab
crontab -e

# Add this line (adjust path):
*/15 * * * * cd /path/to/khan-invoice && php artisan whatsapp:run-followups >> /dev/null 2>&1
```

Or add to Laravel scheduler in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('whatsapp:run-followups')
        ->everyFifteenMinutes()
        ->withoutOverlapping();
}
```

Then ensure Laravel scheduler cron is running:
```bash
* * * * * cd /path/to/khan-invoice && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Test the Integration

#### Test 1: Webhook Verification
```bash
# From your local machine, test the webhook endpoint
curl "https://kinvoice.ng/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=super-secret-token&hub.challenge=test123"

# Expected response: test123
```

#### Test 2: Check Routes
```bash
# On production server
php artisan route:list | grep whatsapp

# Should show:
# GET|HEAD  api/webhooks/whatsapp ........ whatsapp.webhook.verify
# POST      api/webhooks/whatsapp ........ whatsapp.webhook.receive
```

#### Test 3: Send Test Message
1. Send a WhatsApp message to your business number
2. Check logs: `tail -f storage/logs/laravel.log`
3. Should see: "WhatsApp webhook received"
4. Check database: `SELECT * FROM wa_messages ORDER BY created_at DESC LIMIT 5;`

### 9. Configure WhatsApp Settings in Filament

1. Login to Filament admin panel: `https://kinvoice.ng/admin`
2. Navigate to **WhatsApp → WhatsApp Settings**
3. Enter credentials:
   - **Provider**: Meta (Cloud API)
   - **Phone Number ID**: From Meta dashboard
   - **WABA ID**: From Meta dashboard
   - **Access Token**: Your permanent token
   - **Verify Token**: `super-secret-token`
4. Click **Save Settings**
5. Click **Test Connection**

### 10. Create First Automation Rule

1. Navigate to **WhatsApp → Automation Rules**
2. Click **Create**
3. Configure:
   - **Rule Name**: "Unpaid Invoice Reminder - 3 Day Schedule"
   - **Type**: Unpaid Invoice Follow-up
   - **Active**: Yes
   - **Schedule**:
     - Attempt 1: `60` (1 hour after invoice sent)
     - Attempt 2: `1440` (24 hours)
     - Attempt 3: `4320` (3 days)
   - **Message Template**:
     ```
     Hi {{customer_name}},

     This is a friendly reminder about Invoice {{invoice_number}} for {{currency}} {{amount}}.

     Due date: {{due_date}}
     Days overdue: {{days_overdue}}

     Please make payment here: {{payment_link}}

     Thank you!
     {{business_name}}
     ```
4. Click **Create**

---

## 🔍 Verification Checklist

After deployment, verify:

- [ ] All migrations ran successfully (check `migrate:status`)
- [ ] WhatsApp routes are accessible (`route:list | grep whatsapp`)
- [ ] Queue worker is running (`supervisorctl status` or `queue:work --once`)
- [ ] Meta webhook is verified (green checkmark in Meta dashboard)
- [ ] Webhook receives test messages (check logs)
- [ ] Filament WhatsApp Settings page loads
- [ ] Automation Rules can be created
- [ ] Cron job is scheduled (`crontab -l`)
- [ ] Environment variables are set (check `.env`)

---

## 📊 Monitoring

### Key Logs to Monitor

```bash
# Application logs
tail -f storage/logs/laravel.log

# Queue worker logs (if using Supervisor)
tail -f /var/log/supervisor/khan-invoice-worker.log

# Filter WhatsApp logs only
tail -f storage/logs/laravel.log | grep -i whatsapp
```

### Database Queries for Monitoring

```sql
-- Recent WhatsApp messages
SELECT id, direction, body, status, created_at
FROM wa_messages
ORDER BY created_at DESC
LIMIT 20;

-- Failed messages
SELECT id, body, status, error_message
FROM wa_messages
WHERE status = 'failed'
ORDER BY created_at DESC;

-- Active conversations
SELECT c.id, ct.name, ct.phone_e164, c.state, c.status
FROM wa_conversations c
JOIN wa_contacts ct ON c.wa_contact_id = ct.id
WHERE c.status = 'open'
ORDER BY c.updated_at DESC;

-- Conversations needing handoff
SELECT * FROM wa_conversations
WHERE human_handoff = 1 AND status != 'closed'
ORDER BY updated_at DESC;

-- Automation logs (last 24 hours)
SELECT * FROM automation_logs
WHERE created_at >= NOW() - INTERVAL 24 HOUR
ORDER BY created_at DESC;
```

---

## 🚨 Troubleshooting

### Issue: Webhook Not Receiving Messages

**Check:**
1. Meta webhook configuration is correct
2. URL is publicly accessible: `curl https://kinvoice.ng/api/webhooks/whatsapp`
3. SSL certificate is valid
4. Firewall allows incoming HTTPS traffic
5. Check Laravel logs for errors

**Solution:**
```bash
# Test webhook manually
php artisan tinker
>>> app(\App\Http\Controllers\WhatsApp\WhatsAppWebhookController::class)->verify(request());
```

### Issue: Messages Not Sending

**Check:**
1. Queue worker is running: `ps aux | grep queue:work`
2. Access token is valid
3. Phone Number ID is correct
4. Check failed jobs: `php artisan queue:failed`

**Solution:**
```bash
# Restart queue worker
php artisan queue:restart

# Retry failed jobs
php artisan queue:retry all
```

### Issue: AI Not Responding

**Check:**
1. `WA_AI_ENABLED=true` in `.env`
2. OpenAI API key is valid
3. Check API rate limits
4. Review automation logs

**Solution:**
```bash
# Test AI endpoint manually
php artisan tinker
>>> $orchestrator = app(\App\Services\WhatsApp\WhatsAppAiOrchestrator::class);
>>> $orchestrator->isEnabled();
```

### Issue: Follow-ups Not Sending

**Check:**
1. Cron job is running: `crontab -l`
2. Automation rules are active
3. Invoices have WhatsApp contacts
4. Check automation logs

**Solution:**
```bash
# Run follow-ups manually with dry-run
php artisan whatsapp:run-followups --dry-run

# Run for real
php artisan whatsapp:run-followups
```

---

## 📞 Support Resources

- **Documentation**: See `WHATSAPP_IMPLEMENTATION.md`
- **Meta API Docs**: https://developers.facebook.com/docs/whatsapp/cloud-api
- **Laravel Queue Docs**: https://laravel.com/docs/queues
- **Filament Docs**: https://filamentphp.com/docs

---

## 🎯 Post-Deployment Tasks

After successful deployment:

1. **Send Test Message**: Send a test message to your WhatsApp business number
2. **Create Test Invoice**: Create an invoice via WhatsApp conversation
3. **Test Follow-up**: Create unpaid invoice and wait for follow-up (or run command manually)
4. **Monitor Logs**: Watch logs for 24 hours to catch any issues
5. **Set Up Alerts**: Configure monitoring/alerting for failed jobs
6. **Document Credentials**: Save Meta credentials in secure password manager
7. **Train Team**: Show team how to use WhatsApp Inbox in Filament

---

**Deployment Date**: _____________
**Deployed By**: _____________
**Meta Phone Number ID**: _____________
**Webhook URL**: https://kinvoice.ng/api/webhooks/whatsapp

---

✅ **All systems ready for production!**
