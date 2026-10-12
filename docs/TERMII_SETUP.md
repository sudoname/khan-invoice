# Termii SMS & WhatsApp Setup Guide

This application uses **Termii** for both SMS and WhatsApp messaging. Termii is a Nigerian communications platform that provides reliable messaging services.

## Why Termii?

- **Nigeria-focused**: Optimized for Nigerian phone numbers and networks
- **Unified platform**: Single provider for both SMS and WhatsApp
- **Reliable delivery**: High delivery rates for Nigerian carriers
- **Simple integration**: Easy-to-use REST API

## Setup Instructions

### 1. Create a Termii Account

1. Go to [https://termii.com](https://termii.com)
2. Click **Sign Up** and create an account
3. Complete email verification
4. Log in to your dashboard

### 2. Get Your API Credentials

#### API Key:
1. In the Termii dashboard, go to **Settings** → **API**
2. Copy your **API Key**
3. Store it securely - you'll add it to `.env`

#### Sender ID (Optional but recommended):
1. Go to **Settings** → **Sender ID**
2. Request a Sender ID (e.g., "KhanInvoice")
3. Submit required documentation for approval
4. Once approved, you can use your branded Sender ID
5. Until approved, use the default "Termii" sender ID

### 3. Configure Your Application

Add these environment variables to your `.env` file:

```bash
# Termii Configuration
TERMII_API_KEY=your_actual_api_key_here
TERMII_SENDER_ID=KhanInvoice
```

**Example:**
```bash
TERMII_API_KEY=TLmxK8j9P2wR5nQ7vBhT3cX6fYgZ4sA1
TERMII_SENDER_ID=KhanInvoice
```

### 4. Add Credits to Your Account

Termii operates on a prepaid credit system:

1. Log in to your Termii dashboard
2. Go to **Billing** → **Add Credits**
3. Choose your payment method (Card, Bank Transfer, etc.)
4. Add credits based on your expected usage

**Pricing Guide** (approximate):
- SMS: ₦2 - ₦4 per message (depending on volume)
- WhatsApp: ₦5 - ₦10 per message
- Check [Termii Pricing](https://termii.com/pricing) for current rates

### 5. Enable WhatsApp Messaging

For WhatsApp messaging to work:

1. In Termii dashboard, go to **Channels** → **WhatsApp**
2. Follow the setup wizard to:
   - Connect your WhatsApp Business account
   - Verify your business
   - Configure WhatsApp templates (if using template messages)
3. Wait for WhatsApp approval (usually 1-3 business days)

### 6. Test Your Configuration

Once configured, test your setup using the built-in test command:

```bash
# Test both SMS and WhatsApp
php artisan test:messaging +2348168166109

# Test SMS only
php artisan test:messaging +2348168166109 --sms

# Test WhatsApp only
php artisan test:messaging +2348168166109 --whatsapp
```

**Expected Output:**
```
Testing messaging services to: +2348168166109

Testing SMS...
✓ SMS sent successfully!
  Message ID: 123456789

Testing WhatsApp...
✓ WhatsApp sent successfully!
  Message ID: 987654321

Test completed!
```

## Phone Number Format

The application automatically normalizes Nigerian phone numbers:

- `08012345678` → `+2348012345678`
- `2348012345678` → `+2348012345678`
- `+2348012345678` → `+2348012345678` (no change)

For international numbers, use full E.164 format: `+[country_code][number]`

## Credit Management

### Application Credits
Users have internal credits tracked in the `notification_preferences` table:
- `sms_credits_remaining`: SMS credits allocated to user
- `whatsapp_credits_remaining`: WhatsApp credits allocated to user

These are deducted automatically when messages are sent.

### Termii Account Balance
Your Termii account balance is separate. Monitor it in:
1. Termii dashboard
2. Or programmatically: The app logs your balance after each message

**Low Balance Alert**: Set up low balance notifications in Termii dashboard to avoid service interruption.

## Notification Types

The application sends these automated notifications:

| Notification Type | SMS | WhatsApp | Trigger |
|------------------|-----|----------|---------|
| Invoice Sent | ✓ | ✓ | When invoice is emailed to customer |
| Payment Received | ✓ | ✓ | When invoice payment is confirmed |
| Payment Reminder | ✓ | ✓ | X days before due date |
| Invoice Overdue | ✓ | ✓ | When invoice passes due date |

## Troubleshooting

### Error: "Cannot assign null to property"
**Cause**: API key not configured
**Solution**: Add `TERMII_API_KEY` to `.env`

### Error: "Insufficient balance"
**Cause**: Your Termii account has no credits
**Solution**: Add credits in Termii dashboard

### Messages not delivered
**Possible causes:**
1. Invalid phone number format
2. Recipient has blocked sender
3. Network issues
4. WhatsApp not approved yet (for WhatsApp messages)

**Check logs:**
```bash
# View recent SMS logs
php artisan tinker
>>> App\Models\SmsLog::latest()->take(5)->get()

# View recent WhatsApp logs
>>> App\Models\WhatsAppLog::latest()->take(5)->get()
```

### WhatsApp messages failing
**Possible causes:**
1. WhatsApp integration not approved yet
2. Recipient doesn't have WhatsApp
3. Template issues (if using templates)

**Solution**: Check Termii dashboard → WhatsApp → Status

## Production Checklist

Before going live:

- [ ] Termii account created and verified
- [ ] API key added to production `.env`
- [ ] Custom Sender ID approved and configured
- [ ] Sufficient credits added to Termii account
- [ ] WhatsApp integration approved (if using WhatsApp)
- [ ] Test messages sent successfully
- [ ] Low balance alerts configured
- [ ] Notification preferences tested for all notification types
- [ ] SMS and WhatsApp logs being recorded correctly

## Support

- **Termii Support**: support@termii.com
- **Termii Documentation**: [https://developers.termii.com](https://developers.termii.com)
- **Termii Status**: Check [https://status.termii.com](https://status.termii.com) for service status

## Migration from Twilio (If Applicable)

If you previously used Twilio for WhatsApp:

1. Update `.env` with Termii credentials (see step 3 above)
2. Remove old Twilio credentials (optional, but recommended):
   ```bash
   # Remove or comment out:
   # TWILIO_ACCOUNT_SID=...
   # TWILIO_AUTH_TOKEN=...
   # TWILIO_WHATSAPP_FROM=...
   ```
3. Test thoroughly before disabling Twilio account
4. No code changes needed - everything is automatic

## Cost Comparison

**Approximate costs per 1,000 messages:**

| Provider | SMS (Nigeria) | WhatsApp |
|----------|---------------|----------|
| Termii | ₦2,000 - ₦4,000 | ₦5,000 - ₦10,000 |
| Twilio | $75 - $100 (₦120,000+) | $5 - $10 (₦8,000+) |

*Termii is significantly more cost-effective for Nigerian audiences.*

---

**Last Updated**: January 2026
**Application Version**: 1.0
