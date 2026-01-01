# Email Notifications Setup Guide

## Current Issue
Email notifications are currently being **logged to files** instead of being sent. This is because `MAIL_MAILER=log` in your `.env` file.

## Recommended Solution: Use Gmail SMTP

### Step 1: Update .env File
Replace the MAIL settings in your `.env` file with:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Kinvoice"
```

### Step 2: Generate Gmail App Password

1. Go to your Google Account: https://myaccount.google.com
2. Navigate to **Security**
3. Enable **2-Step Verification** (if not already enabled)
4. Scroll down to **2-Step Verification** section
5. Click on **App passwords**
6. Select app: **Mail**
7. Select device: **Other (Custom name)** → Enter "Kinvoice"
8. Click **Generate**
9. Copy the 16-character password
10. Use this password in `MAIL_PASSWORD` (without spaces)

### Step 3: Restart Application
```bash
php artisan config:clear
php artisan cache:clear
```

### Step 4: Test Email
```bash
php artisan tinker
>>> \Illuminate\Support\Facades\Mail::raw('Test email', function($message) { $message->to('test@example.com')->subject('Test'); });
```

---

## Alternative Solutions

### Option 1: Mailgun (Recommended for Production)
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.mailgun.org
MAILGUN_SECRET=your-mailgun-api-key
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS=noreply@kinvoice.ng
MAIL_FROM_NAME="Kinvoice"
```

### Option 2: SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@kinvoice.ng
MAIL_FROM_NAME="Kinvoice"
```

### Option 3: Postmark
```env
MAIL_MAILER=postmark
POSTMARK_TOKEN=your-postmark-token
MAIL_FROM_ADDRESS=noreply@kinvoice.ng
MAIL_FROM_NAME="Kinvoice"
```

---

## What Emails Are Sent?

Your app sends these notifications:
1. **Invoice Sent** - When invoice is marked as sent
2. **Payment Received** - When payment is recorded
3. **Payment Reminder** - Reminder for unpaid invoices
4. **Invoice Overdue** - When invoice becomes overdue
5. **Subscription Changed** - When subscription plan changes
6. **Email Verification** - For new user signups
7. **Password Reset** - When user requests password reset

---

## Troubleshooting

### Emails still not sending?
1. Check `.env` file is updated (no typos)
2. Run `php artisan config:clear`
3. Check `storage/logs/laravel.log` for error messages
4. Verify Gmail app password is correct (no spaces)
5. Make sure 2FA is enabled on Gmail account

### Getting "Connection Timeout" errors?
- Check firewall allows outbound connections on port 587
- Try port 465 with `MAIL_ENCRYPTION=ssl` instead

### Emails going to Spam?
- Use a custom domain email (not Gmail) for production
- Configure SPF, DKIM, and DMARC records
- Consider using Mailgun or SendGrid for better deliverability

---

## Production Recommendations

For production (kinvoice.ng):
1. ✅ Use a professional email service (Mailgun, SendGrid, or Postmark)
2. ✅ Use custom domain: noreply@kinvoice.ng
3. ✅ Configure SPF and DKIM records
4. ✅ Set up email tracking and analytics
5. ✅ Enable queue workers for async email sending:
   ```bash
   php artisan queue:work --daemon
   ```

---

## Queue Setup (Optional but Recommended)

To send emails in the background:

1. Update `.env`:
```env
QUEUE_CONNECTION=database
```

2. Create queue table:
```bash
php artisan queue:table
php artisan migrate
```

3. Start queue worker:
```bash
php artisan queue:work
```

4. For production, use Supervisor to keep queue worker running:
```bash
sudo apt-get install supervisor
sudo nano /etc/supervisor/conf.d/kinvoice-worker.conf
```

Add:
```ini
[program:kinvoice-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/kinvoice.ng/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/kinvoice.ng/storage/logs/worker.log
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start kinvoice-worker:*
```

---

## Need Help?

Check the Laravel docs: https://laravel.com/docs/mail
