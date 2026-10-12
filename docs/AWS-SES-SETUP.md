# AWS SES Setup Guide for Khan Invoice

Complete step-by-step guide to set up Amazon SES for multi-domain email delivery.

## Step 1: Create AWS Account

1. Go to https://aws.amazon.com
2. Click "Create an AWS Account"
3. Fill in:
   - Email address
   - Password
   - AWS account name (e.g., "Khan Invoice Production")
4. Choose **Personal** account type
5. Enter payment information (credit/debit card)
   - You'll be charged $1 for verification (refunded)
6. Verify phone number
7. Choose **Basic Support Plan** (Free)

**Important**: Save your AWS Account ID and root email address.

## Step 2: Access AWS SES

1. Sign in to AWS Console: https://console.aws.amazon.com
2. In the search bar at top, type: **SES**
3. Click **Amazon Simple Email Service**
4. **Select your region**: Choose closest to Nigeria
   - Recommended: **eu-west-1** (Ireland) - closest to Africa
   - Alternative: **us-east-1** (N. Virginia) - global standard

**Note**: Remember your region! You'll need it for configuration.

## Step 3: Verify Your Domain (kinvoice.ng)

### 3.1 Start Domain Verification

1. In SES Console, click **Verified identities** (left sidebar)
2. Click **Create identity** button
3. Select **Domain**
4. Enter domain: `kinvoice.ng`
5. Check: ☑️ **Assign a default configuration set**
6. Check: ☑️ **Use a custom MAIL FROM domain** (optional but recommended)
   - If checked, enter: `mail.kinvoice.ng`
7. Check: ☑️ **Enable DKIM signatures**
8. **DKIM signing key length**: 2048-bit (recommended)
9. Click **Create identity**

### 3.2 DNS Records to Add

AWS will provide you with DNS records to add. You'll need to add these to your domain registrar (where you bought kinvoice.ng).

#### Records You'll Get:

**1. DKIM Records (3 CNAME records)**
```
Type: CNAME
Name: xxxxxxxxx._domainkey.kinvoice.ng
Value: xxxxxxxxx.dkim.amazonses.com
TTL: 1800
```

You'll get 3 of these - add all 3.

**2. MAIL FROM Record (MX record)** - if you enabled custom MAIL FROM
```
Type: MX
Name: mail.kinvoice.ng
Value: 10 feedback-smtp.eu-west-1.amazonses.com
TTL: 1800
```

**3. MAIL FROM SPF Record (TXT record)** - if you enabled custom MAIL FROM
```
Type: TXT
Name: mail.kinvoice.ng
Value: v=spf1 include:amazonses.com ~all
TTL: 1800
```

### 3.3 Add DNS Records

**If using Cloudflare:**
1. Go to cloudflare.com → Your domain → DNS
2. Add each record exactly as shown by AWS
3. For CNAME records: Turn OFF Cloudflare proxy (grey cloud, not orange)

**If using other DNS provider:**
1. Log into your domain registrar
2. Find DNS management
3. Add each record as shown

**Wait 10-15 minutes** for DNS propagation.

### 3.4 Verify Domain Status

Back in AWS SES:
1. Refresh the **Verified identities** page
2. Status should change from "Pending" to "Verified" (green checkmark)
3. If not verified after 30 minutes, check DNS records again

## Step 4: Add SPF and DMARC Records

### 4.1 SPF Record for Main Domain

Add this to your DNS:

```
Type: TXT
Name: @  (or kinvoice.ng)
Value: v=spf1 include:amazonses.com ~all
TTL: 1800
```

**Note**: If you already have an SPF record, modify it to include `include:amazonses.com`

### 4.2 DMARC Record (Recommended)

Add this to your DNS:

```
Type: TXT
Name: _dmarc
Value: v=DMARC1; p=quarantine; rua=mailto:dmarc@kinvoice.ng
TTL: 1800
```

This tells email providers how to handle emails that fail authentication.

## Step 5: Generate SMTP Credentials

1. In SES Console, go to **SMTP settings** (left sidebar)
2. Click **Create SMTP credentials**
3. Enter IAM User Name: `khan-invoice-smtp`
4. Click **Create**
5. **IMPORTANT**: Download or copy these credentials NOW
   - SMTP Username (looks like: AKIAXXXXXXXXXXXXXXXX)
   - SMTP Password (looks like: BH7xXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX)
6. **You can't retrieve the password again!**

### SMTP Endpoint Information

Based on your region:

| Region | SMTP Endpoint |
|--------|---------------|
| eu-west-1 (Ireland) | `email-smtp.eu-west-1.amazonaws.com` |
| us-east-1 (N. Virginia) | `email-smtp.us-east-1.amazonaws.com` |
| us-west-2 (Oregon) | `email-smtp.us-west-2.amazonaws.com` |

**Port**: 587 (TLS)

## Step 6: Update Laravel Configuration

### 6.1 Update Production .env

SSH to your server and edit `.env`:

```bash
ssh root@147.182.242.177
cd /var/www/kinvoice.ng
nano .env
```

Update these lines:

```env
MAIL_MAILER=ses
MAIL_HOST=email-smtp.eu-west-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=AKIAXXXXXXXXXXXXXXXX
MAIL_PASSWORD=BH7xXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="info@kinvoice.ng"
MAIL_FROM_NAME="Khan Invoice"

# Add AWS SES Configuration
AWS_ACCESS_KEY_ID=AKIAXXXXXXXXXXXXXXXX
AWS_SECRET_ACCESS_KEY=BH7xXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
AWS_DEFAULT_REGION=eu-west-1
AWS_SES_REGION=eu-west-1
```

**Replace**:
- `AKIAXXXXXXXXXXXXXXXX` with your SMTP Username
- `BH7xXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX` with your SMTP Password
- `eu-west-1` with your chosen region

Save and exit: `Ctrl+X`, `Y`, `Enter`

### 6.2 Install AWS SDK (if needed)

```bash
cd /var/www/kinvoice.ng
composer require aws/aws-sdk-php
```

### 6.3 Clear Cache

```bash
php artisan config:cache
php artisan cache:clear
```

### 6.4 Update Local .env

Update your local `.env` file with same settings for testing.

## Step 7: Test Email Sending

### 7.1 Test from Server

```bash
ssh root@147.182.242.177
cd /var/www/kinvoice.ng
php artisan email:test your-email@gmail.com
```

Try multiple email providers:
- Gmail
- Yahoo
- Outlook/Hotmail
- Custom domain

### 7.2 Check AWS SES Console

1. Go to SES Console → **Email sending** → **Sending statistics**
2. You should see:
   - Sends: 1
   - Deliveries: 1
   - Bounces: 0

## Step 8: Request Production Access (IMPORTANT!)

By default, AWS SES is in **Sandbox Mode**, which means:
- ❌ Can only send to verified email addresses
- ❌ Can't send to random users
- ❌ Limited to 200 emails/day

### 8.1 Request Production Access

1. In SES Console, click **Account dashboard** (left sidebar)
2. Click **Request production access** button
3. Fill out the form:

**Mail Type**: Transactional

**Website URL**: https://kinvoice.ng

**Use case description** (example):
```
Khan Invoice is an invoicing and billing platform that sends transactional
emails to registered users. Email types include:

1. Email verification for new user registrations
2. Invoice notifications when invoices are created/sent
3. Payment confirmation emails
4. Password reset requests
5. Account notifications

All emails are opt-in and sent only to users who register on our platform.
We have implemented bounce and complaint handling.

Expected volume: 500-2,000 emails per month initially.
```

**Additional comments** (example):
```
We are migrating from Gmail SMTP to AWS SES for better deliverability.
All recipients are legitimate users of our invoicing platform.
```

**Compliance**: Check all boxes confirming compliance

**Acknowledge**: Check box

Click **Submit request**

### 8.2 Wait for Approval

- Usually approved in **24-48 hours**
- Check email for AWS response
- Until approved, you can only test with verified email addresses

### 8.3 Verify Test Email Addresses (While Waiting)

To test before production approval:
1. In SES Console → **Verified identities**
2. Click **Create identity**
3. Select **Email address**
4. Enter test email: `test@yahoo.com`
5. Check that email and click verification link
6. Repeat for other test emails

## Step 9: Configure Bounce and Complaint Handling

### 9.1 Set Up SNS Topics (Recommended)

1. Go to **Configuration sets** in SES Console
2. Click **Create configuration set**
3. Name: `khan-invoice-default`
4. Create event destinations:
   - Bounce
   - Complaint
   - Delivery

### 9.2 Update Laravel to Handle Bounces

Create: `app/Services/SesWebhookHandler.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SesWebhookHandler
{
    public function handleBounce($message)
    {
        $email = $message['bounce']['bouncedRecipients'][0]['emailAddress'];
        Log::warning('Email bounced: ' . $email);

        // TODO: Mark user email as invalid
        // User::where('email', $email)->update(['email_verified_at' => null]);
    }

    public function handleComplaint($message)
    {
        $email = $message['complaint']['complainedRecipients'][0]['emailAddress'];
        Log::warning('Email complaint from: ' . $email);

        // TODO: Unsubscribe user from emails
    }
}
```

## Step 10: Monitor Email Sending

### 10.1 AWS SES Dashboard

Monitor:
- **Bounce rate**: Should be < 5%
- **Complaint rate**: Should be < 0.1%
- **Delivery rate**: Should be > 95%

### 10.2 Reputation Dashboard

1. Go to **Reputation metrics** in SES Console
2. Keep bounce/complaint rates low
3. If rates are high, AWS may suspend your account

## Multi-Domain Setup (For Future)

When businesses want to send from their own domains:

### For Each New Domain:

1. **Verify the domain** (Step 3)
2. **Add DNS records** to their domain
3. **Wait for verification**
4. **Update email FROM address** dynamically in code

### Code Example:

```php
use Illuminate\Support\Facades\Mail;

// Get business profile
$business = $invoice->businessProfile;

// Set dynamic FROM address
Mail::from($business->email, $business->business_name)
    ->to($customer->email)
    ->send(new InvoiceCreated($invoice));
```

## Troubleshooting

### Emails Not Sending

1. **Check AWS SES Console**:
   - Go to **Email sending** → **Sending statistics**
   - Look for bounces or errors

2. **Check Laravel Logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Verify DNS Records**:
   ```bash
   dig CNAME xxxxxxxxx._domainkey.kinvoice.ng
   dig TXT kinvoice.ng
   ```

4. **Test SMTP Connection**:
   ```bash
   telnet email-smtp.eu-west-1.amazonaws.com 587
   ```

### Domain Not Verifying

- Wait 30 minutes after adding DNS records
- Check DNS records are exactly as AWS specified
- No extra quotes or spaces in TXT records
- CNAME records: Cloudflare proxy disabled

### Yahoo/Outlook Still Not Receiving

- Check spam folder
- Verify DMARC record is added
- Ensure bounce rate is low
- Check domain reputation at: https://mxtoolbox.com

### Still in Sandbox Mode

- Request production access (Step 8)
- While waiting, verify recipient email addresses
- Check AWS email for approval/rejection

## Cost Monitoring

### Set Up Billing Alerts

1. Go to **AWS Billing Dashboard**
2. Click **Budgets**
3. **Create budget**:
   - Budget type: Cost budget
   - Amount: $10/month (adjust as needed)
4. Set email alerts at:
   - 50% ($5)
   - 80% ($8)
   - 100% ($10)

### View Current Costs

1. **AWS Billing Dashboard**
2. Click **Bills**
3. Check **Amazon Simple Email Service** line item

Expected monthly cost for Khan Invoice: **$0-5/month**

## Summary

Once set up, you'll have:
- ✅ Professional email delivery
- ✅ Multiple domain support
- ✅ Better deliverability (Yahoo, Outlook, etc.)
- ✅ 99.99% uptime
- ✅ Very low cost ($0.10 per 1,000 emails)
- ✅ Detailed analytics
- ✅ Scalable to unlimited users

## Support Resources

- AWS SES Documentation: https://docs.aws.amazon.com/ses/
- Laravel SES Driver: https://laravel.com/docs/mail#ses-driver
- AWS Support: https://console.aws.amazon.com/support/
- MX Toolbox (test emails): https://mxtoolbox.com/

---

**Ready to go live?** Follow these steps in order and you'll have professional email delivery in about 1 hour!
