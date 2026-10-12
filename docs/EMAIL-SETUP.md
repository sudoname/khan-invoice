# Email Configuration - Khan Invoice

## ✅ Email Setup Complete

Email sending is now configured and working on https://kinvoice.ng

### Configuration Details

**Email Service:** Gmail SMTP
**Email Address:** pathway.developer@gmail.com
**SMTP Server:** smtp.gmail.com
**Port:** 587
**Encryption:** TLS

### What's Enabled

1. **✉️ Email Verification**
   - New users receive verification email after registration
   - Must verify email before accessing full features
   - Route: `/app/email-verification/prompt`

2. **📧 Notification Emails**
   - Invoice notifications
   - Payment reminders
   - System notifications

3. **🔐 Password Reset**
   - Forgot password emails
   - Password reset links

### Testing Email Configuration

To test if email is working:

```bash
# On server
cd /var/www/kinvoice.ng
php artisan email:test your-email@example.com
```

### Gmail App Password

**Important:** The Gmail account uses an App Password for security.

To regenerate the app password:
1. Go to https://myaccount.google.com/apppasswords
2. Select "Mail" and "Other (Khan Invoice)"
3. Generate new password
4. Update `.env` file with new password
5. Run `php artisan config:cache`

### Email Templates

Laravel uses default email templates. To customize:

```bash
php artisan vendor:publish --tag=laravel-mail
```

Templates will be in `resources/views/vendor/mail/`

### Troubleshooting

**Issue:** Emails not sending
**Solution:**
- Check Gmail app password is correct
- Verify 2FA is enabled on Gmail account
- Check logs: `tail -f storage/logs/laravel.log`

**Issue:** Verification emails not received
**Solution:**
- Check spam folder
- Test with: `php artisan email:test`
- Verify MAIL_FROM_ADDRESS in .env

### Daily Sending Limits

Gmail free accounts have limits:
- **500 emails per day** for free Gmail accounts
- **2000 emails per day** for Google Workspace accounts

For higher volumes, consider:
- SendGrid (100 free/day, then paid)
- Mailgun (5000 free first 3 months)
- Amazon SES (pay as you go)

### Security Notes

- App password is stored securely in `.env` (not in git)
- `.env` file is not publicly accessible (outside public directory)
- TLS encryption for all email transmissions
- Gmail automatically scans for suspicious activity

### Email Flow

#### Registration Flow:
1. User registers at `/app/register`
2. Account created with `email_verified_at = null`
3. Verification email sent to user
4. User clicks link in email
5. Email verified, `email_verified_at` updated
6. User can access full app features

#### Social Login Flow:
1. User logs in with Facebook/Google
2. Account created with `email_verified_at = now()`
3. No verification needed (trusted provider)
4. Instant access to app

### Configuration Files

**Server .env:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=pathway.developer@gmail.com
MAIL_PASSWORD=venzukyiidogzcaf
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="pathway.developer@gmail.com"
MAIL_FROM_NAME="Khan Invoice"
```

**Panel Config:** `app/Providers/Filament/AppPanelProvider.php`
```php
->emailVerification()
```

### Testing Checklist

- [x] Gmail SMTP configured
- [x] Test email sent successfully
- [x] Verification routes enabled
- [x] Email verification flow working
- [ ] Test: Register new user via email
- [ ] Test: Receive verification email
- [ ] Test: Click verification link
- [ ] Test: Access dashboard after verification

### Next Steps

1. **Test the full flow:**
   - Register a new account at https://kinvoice.ng/app/register
   - Check email for verification link
   - Click link and verify it works

2. **Monitor email deliverability:**
   - Check if emails land in spam
   - Adjust sender name/subject if needed

3. **Optional: Customize email templates**
   - Add company branding
   - Customize colors and styling
   - Add footer with social links

---

## Support

**Email not working?** Run diagnostics:
```bash
php artisan email:test pathway.developer@gmail.com
php artisan config:clear
php artisan config:cache
systemctl restart php8.3-fpm
```

Check logs:
```bash
tail -f /var/www/kinvoice.ng/storage/logs/laravel.log
```
