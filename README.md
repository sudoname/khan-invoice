# Khan Invoice

A modern, feature-rich invoicing application built with Laravel. Khan Invoice helps businesses create, manage, and track invoices with built-in AI-powered features for automation and insights.

## Features

### Core Functionality
- **Invoice Management**: Create, edit, and manage professional invoices
- **Customer Management**: Track customer information and history
- **Business Profiles**: Support for multiple business profiles
- **Payment Tracking**: Monitor payments and invoice status
- **PDF Generation**: Generate professional PDF invoices
- **Public Invoice Links**: Share invoices via unique public URLs
- **Invoice Verification**: SHA-256 document hash for invoice authenticity

### AI-Powered Features
Khan Invoice includes three deterministic AI modules that use historical data to provide intelligent suggestions and insights:

1. **Smart Suggestions**
   - Customer autofill based on usage patterns
   - Line item suggestions from history
   - Due date recommendations based on payment patterns
   - Weighted scoring (recency: 70%, frequency: 30%)

2. **Payment Reminders**
   - Automated reminder scheduling
   - Multi-channel support (Email, WhatsApp, SMS)
   - Business hours and weekend handling
   - Customizable reminder schedule

3. **Analytics Insights**
   - Payment pattern analysis
   - Late payment identification
   - Revenue trend analysis (12 months)
   - Top customer rankings
   - Invoice statistics

## Requirements

- PHP 8.2+
- Composer
- MySQL/PostgreSQL (or SQLite for development)
- Node.js & NPM (for frontend assets)
- Queue worker (for background jobs)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/khan-invoice.git
cd khan-invoice
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Create environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure your database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=khan_invoice
DB_USERNAME=root
DB_PASSWORD=
```

6. Run migrations:
```bash
php artisan migrate
```

7. Seed the database (optional):
```bash
php artisan db:seed
```

8. Build frontend assets:
```bash
npm run build
```

9. Start the development server:
```bash
php artisan serve
```

10. Start the queue worker (required for AI features):
```bash
php artisan queue:work
```

## Configuration

### AI Features

AI features can be enabled/disabled via environment variables in your `.env` file:

```env
# Master toggle for all AI features
KINVOICE_AI_ENABLED=true

# Smart Suggestions (Customer/Item autofill, Due date suggestions)
KINVOICE_AI_SUGGESTIONS_ENABLED=true

# Payment Reminders
KINVOICE_AI_REMINDERS_ENABLED=false
KINVOICE_AI_REMINDERS_AUTO_SEND=false

# Analytics Insights
KINVOICE_AI_INSIGHTS_ENABLED=true
```

**Important**: Payment reminders require configured email/SMS/WhatsApp services. Keep `KINVOICE_AI_REMINDERS_AUTO_SEND=false` until you've tested your messaging configuration.

### Queue Configuration

AI features use Laravel queues for background processing. Ensure you have a queue worker running:

```bash
# Development
php artisan queue:work

# Production (with supervisor)
# See: https://laravel.com/docs/queues#supervisor-configuration
```

### Payment Gateway Configuration

Khan Invoice supports Paystack and Flutterwave. Configure in your `.env`:

```env
PAYSTACK_PUBLIC_KEY=pk_test_xxxxx
PAYSTACK_SECRET_KEY=sk_test_xxxxx
```

### Social Authentication

Configure OAuth providers in `.env`:

```env
FACEBOOK_CLIENT_ID=your_facebook_app_id
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

## API Documentation

### Authentication

All API endpoints require authentication via Laravel Sanctum. Include your API token in the request header:

```
Authorization: Bearer YOUR_API_TOKEN
```

### AI Endpoints

#### Smart Suggestions

**Suggest Customers**
```http
GET /api/v1/ai/suggest/customers?q=search_term
```

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Acme Corporation",
      "email": "contact@acme.com",
      "score": 0.95
    }
  ],
  "meta": {
    "count": 1,
    "query": "Acme",
    "duration_ms": 45.2
  }
}
```

**Suggest Line Items**
```http
GET /api/v1/ai/suggest/items?q=search_term&customer_id=123
```

**Suggest Due Date**
```http
GET /api/v1/ai/suggest/due-date?customer_id=123
```

#### Payment Reminders

**Plan Reminders (Preview)**
```http
GET /api/v1/ai/reminders/plan/{invoice_id}
```

**Create Reminder Plan**
```http
POST /api/v1/ai/reminders/{invoice_id}
Content-Type: application/json

{
  "channel": "email"
}
```

#### Analytics Insights

**Get All Insights**
```http
GET /api/v1/ai/insights
```

**Get Statistics**
```http
GET /api/v1/ai/stats
```

### Rate Limiting

AI endpoints have separate rate limits:
- Suggestions: 60 requests/minute per user
- Reminders: 10 requests/minute per user
- Insights: 30 requests/minute per user

## Invoice Document Hash

Khan Invoice implements SHA-256 document hashing for invoice verification:

### Features
- Deterministic hash generation from invoice content
- Automatic hash updates when invoice data changes
- Public display of hash on invoice views
- Backfill command for existing invoices

### Usage

**Backfill Hashes**
```bash
# Backfill all invoices
php artisan invoices:backfill-hashes

# Backfill only private invoices
php artisan invoices:backfill-hashes --type=private

# Force rehash even if hash exists
php artisan invoices:backfill-hashes --force
```

**Verify Invoice**

The document hash is displayed on both authenticated and public invoice views. Users can copy the hash to verify invoice authenticity.

For detailed implementation information, see [INVOICE_HASH_IMPLEMENTATION.md](./INVOICE_HASH_IMPLEMENTATION.md).

## Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run AI module tests
php artisan test tests/Unit/SuggestionServiceTest.php
php artisan test tests/Unit/ReminderPlannerServiceTest.php
php artisan test tests/Unit/InsightsServiceTest.php
php artisan test tests/Feature/AIApiTest.php
```

## Deployment

### Production Checklist

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Configure production database
3. Set up queue workers with Supervisor
4. Configure email/SMS services for reminders
5. Set up cron for scheduled tasks:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```
6. Run migrations:
```bash
php artisan migrate --force
```
7. Optimize Laravel:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
8. Set proper file permissions
9. Configure SSL certificate
10. Backfill invoice hashes:
```bash
php artisan invoices:backfill-hashes
```

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For issues, questions, or feature requests, please open an issue on GitHub.

## Acknowledgments

- Built with [Laravel](https://laravel.com)
- PDF generation via [DomPDF](https://github.com/dompdf/dompdf)
- Payment processing by [Paystack](https://paystack.com) and [Flutterwave](https://flutterwave.com)
- Analytics tracking with [Google Analytics 4](https://analytics.google.com)
