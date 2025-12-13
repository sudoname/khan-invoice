# Khan Invoice REST API Documentation

## Overview

Khan Invoice provides a comprehensive REST API that allows you to integrate invoicing functionality into your applications. The API is built using Laravel Sanctum for authentication and follows RESTful conventions.

**Base URL:** `http://your-domain.com/api/v1`

**Authentication:** Bearer Token (via Sanctum)

**Response Format:** JSON

---

## Authentication

### Create API Token

Create a new API token for accessing protected endpoints.

**Endpoint:** `POST /api/v1/auth/token`

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "your_password",
  "token_name": "My Application Token" // Optional
}
```

**Success Response (201 Created):**
```json
{
  "message": "Token created successfully",
  "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
  "token_name": "My Application Token",
  "abilities": ["*"]
}
```

**Error Responses:**
- `422 Unprocessable Entity` - Validation error
- `403 Forbidden` - API access not enabled for account

---

### Revoke Current Token

Revoke the token used in the request.

**Endpoint:** `POST /api/v1/auth/revoke`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
```

**Success Response (200 OK):**
```json
{
  "message": "Token revoked successfully"
}
```

---

## Invoices

### List All Invoices

Get a paginated list of all invoices for the authenticated user.

**Endpoint:** `GET /api/v1/invoices`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Query Parameters:**
- `page` (integer) - Page number for pagination
- `per_page` (integer) - Items per page (max: 100)
- `status` (string) - Filter by status: draft, sent, paid, partially_paid, overdue, cancelled
- `customer_id` (integer) - Filter by customer ID

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "invoice_number": "INV-2025-00001",
      "customer_id": 1,
      "business_profile_id": 1,
      "issue_date": "2025-12-12",
      "due_date": "2026-01-12",
      "status": "sent",
      "currency": "USD",
      "subtotal": 1000.00,
      "tax_amount": 100.00,
      "discount_amount": 0.00,
      "total_amount": 1100.00,
      "paid_amount": 0.00,
      "notes": "Payment terms: Net 30",
      "created_at": "2025-12-12T10:00:00.000000Z",
      "updated_at": "2025-12-12T10:00:00.000000Z"
    }
  ],
  "links": {
    "first": "http://domain.com/api/v1/invoices?page=1",
    "last": "http://domain.com/api/v1/invoices?page=10",
    "prev": null,
    "next": "http://domain.com/api/v1/invoices?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

---

### Get Single Invoice

Retrieve a specific invoice by ID.

**Endpoint:** `GET /api/v1/invoices/{id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "invoice_number": "INV-2025-00001",
    "customer": {
      "id": 1,
      "name": "John Doe",
      "company_name": "Acme Corp",
      "email": "john@acme.com"
    },
    "business_profile": {
      "id": 1,
      "business_name": "My Business",
      "email": "business@example.com"
    },
    "items": [
      {
        "description": "Web Development Services",
        "quantity": 1,
        "unit_price": 1000.00,
        "total": 1000.00
      }
    ],
    "issue_date": "2025-12-12",
    "due_date": "2026-01-12",
    "status": "sent",
    "currency": "USD",
    "subtotal": 1000.00,
    "tax_amount": 100.00,
    "discount_amount": 0.00,
    "total_amount": 1100.00,
    "paid_amount": 0.00,
    "notes": "Payment terms: Net 30",
    "created_at": "2025-12-12T10:00:00.000000Z",
    "updated_at": "2025-12-12T10:00:00.000000Z"
  }
}
```

**Error Response:**
- `404 Not Found` - Invoice not found or not accessible

---

### Create Invoice

Create a new invoice.

**Endpoint:** `POST /api/v1/invoices`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "customer_id": 1,
  "business_profile_id": 1,
  "issue_date": "2025-12-12",
  "due_date": "2026-01-12",
  "currency": "USD",
  "items": [
    {
      "description": "Web Development Services",
      "quantity": 1,
      "unit_price": 1000.00
    },
    {
      "description": "Hosting (Annual)",
      "quantity": 1,
      "unit_price": 200.00
    }
  ],
  "tax_rate": 10,
  "discount_amount": 0,
  "notes": "Payment terms: Net 30"
}
```

**Success Response (201 Created):**
```json
{
  "data": {
    "id": 2,
    "invoice_number": "INV-2025-00002",
    "customer_id": 1,
    "business_profile_id": 1,
    "status": "draft",
    "currency": "USD",
    "total_amount": 1320.00,
    "created_at": "2025-12-12T11:00:00.000000Z"
  }
}
```

**Error Response:**
- `422 Unprocessable Entity` - Validation error

---

### Update Invoice

Update an existing invoice.

**Endpoint:** `PUT /api/v1/invoices/{id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Request Body:** (All fields optional)
```json
{
  "status": "sent",
  "due_date": "2026-02-12",
  "notes": "Updated payment terms"
}
```

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 2,
    "invoice_number": "INV-2025-00002",
    "status": "sent",
    "due_date": "2026-02-12",
    "updated_at": "2025-12-12T11:30:00.000000Z"
  }
}
```

**Error Responses:**
- `404 Not Found` - Invoice not found
- `422 Unprocessable Entity` - Validation error

---

### Delete Invoice

Delete an invoice (soft delete).

**Endpoint:** `DELETE /api/v1/invoices/{id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Success Response (204 No Content)**

**Error Response:**
- `404 Not Found` - Invoice not found

---

## Customers

### List All Customers

Get a paginated list of all customers.

**Endpoint:** `GET /api/v1/customers`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Query Parameters:**
- `page` (integer) - Page number
- `per_page` (integer) - Items per page
- `search` (string) - Search by name or email

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "company_name": "Acme Corp",
      "email": "john@acme.com",
      "phone": "+1234567890",
      "address": "123 Main St",
      "city": "New York",
      "state": "NY",
      "country": "USA",
      "postal_code": "10001",
      "is_active": true,
      "created_at": "2025-12-01T10:00:00.000000Z"
    }
  ]
}
```

---

### Get Single Customer

Retrieve a specific customer by ID.

**Endpoint:** `GET /api/v1/customers/{id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "company_name": "Acme Corp",
    "email": "john@acme.com",
    "phone": "+1234567890",
    "address": "123 Main St",
    "city": "New York",
    "state": "NY",
    "country": "USA",
    "postal_code": "10001",
    "tax_id": "12-3456789",
    "notes": "VIP customer",
    "is_active": true,
    "invoices_count": 15,
    "total_owed": 5000.00,
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

---

### Create Customer

Create a new customer.

**Endpoint:** `POST /api/v1/customers`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "Jane Smith",
  "company_name": "Tech Solutions Inc",
  "email": "jane@techsolutions.com",
  "phone": "+1234567890",
  "address": "456 Tech Ave",
  "city": "San Francisco",
  "state": "CA",
  "country": "USA",
  "postal_code": "94102",
  "tax_id": "98-7654321",
  "notes": "New customer",
  "is_active": true
}
```

**Success Response (201 Created):**
```json
{
  "data": {
    "id": 2,
    "name": "Jane Smith",
    "email": "jane@techsolutions.com",
    "created_at": "2025-12-12T12:00:00.000000Z"
  }
}
```

---

### Update Customer

Update an existing customer.

**Endpoint:** `PUT /api/v1/customers/{id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Request Body:** (All fields optional)
```json
{
  "phone": "+1987654321",
  "notes": "Updated contact information"
}
```

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 2,
    "name": "Jane Smith",
    "phone": "+1987654321",
    "updated_at": "2025-12-12T12:30:00.000000Z"
  }
}
```

---

### Delete Customer

Delete a customer (soft delete).

**Endpoint:** `DELETE /api/v1/customers/{id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Success Response (204 No Content)**

---

## Payments

### List All Payments

Get a paginated list of all payments.

**Endpoint:** `GET /api/v1/payments`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Query Parameters:**
- `page` (integer) - Page number
- `per_page` (integer) - Items per page
- `invoice_id` (integer) - Filter by invoice ID

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "invoice_id": 1,
      "amount": 1100.00,
      "payment_method": "bank_transfer",
      "payment_date": "2025-12-15",
      "reference_number": "TXN123456",
      "status": "completed",
      "invoice": {
        "id": 1,
        "invoice_number": "INV-2025-00001",
        "customer_name": "John Doe",
        "total_amount": 1100.00,
        "currency": "USD"
      },
      "created_at": "2025-12-15T10:00:00.000000Z"
    }
  ]
}
```

---

### Record Payment

Record a new payment for an invoice.

**Endpoint:** `POST /api/v1/payments`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "invoice_id": 1,
  "amount": 1100.00,
  "payment_method": "bank_transfer",
  "payment_date": "2025-12-15",
  "reference_number": "TXN123456",
  "notes": "Full payment received"
}
```

**Success Response (201 Created):**
```json
{
  "data": {
    "id": 2,
    "invoice_id": 1,
    "amount": 1100.00,
    "payment_method": "bank_transfer",
    "status": "completed",
    "created_at": "2025-12-15T11:00:00.000000Z"
  }
}
```

---

## Reports

### Sales Report

Get a sales report for a specified date range.

**Endpoint:** `GET /api/v1/reports/sales`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Query Parameters:**
- `start_date` (required, date) - Start date (YYYY-MM-DD)
- `end_date` (required, date) - End date (YYYY-MM-DD)
- `currency` (optional, string) - Filter by currency

**Example Request:**
```
GET /api/v1/reports/sales?start_date=2025-01-01&end_date=2025-12-31&currency=USD
```

**Success Response (200 OK):**
```json
{
  "period": {
    "start_date": "2025-01-01",
    "end_date": "2025-12-31"
  },
  "summary": {
    "total_invoices": 150,
    "total_amount": 165000.00,
    "paid_amount": 145000.00,
    "unpaid_amount": 20000.00
  },
  "by_status": {
    "paid": {
      "count": 120,
      "total": 145000.00
    },
    "sent": {
      "count": 20,
      "total": 15000.00
    },
    "overdue": {
      "count": 10,
      "total": 5000.00
    }
  },
  "by_currency": {
    "USD": {
      "count": 100,
      "total": 120000.00
    },
    "EUR": {
      "count": 50,
      "total": 45000.00
    }
  },
  "invoices": [
    {
      "id": 1,
      "invoice_number": "INV-2025-00001",
      "customer_name": "John Doe",
      "issue_date": "2025-01-15",
      "due_date": "2025-02-15",
      "status": "paid",
      "currency": "USD",
      "total_amount": 1100.00
    }
  ]
}
```

---

### Aging Report

Get an accounts receivable aging report.

**Endpoint:** `GET /api/v1/reports/aging`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Success Response (200 OK):**
```json
{
  "as_of_date": "2025-12-12",
  "summary": {
    "current": {
      "count": 5,
      "total": 5000.00
    },
    "1-30_days": {
      "count": 3,
      "total": 3000.00
    },
    "31-60_days": {
      "count": 2,
      "total": 2000.00
    },
    "61-90_days": {
      "count": 1,
      "total": 1000.00
    },
    "over_90_days": {
      "count": 1,
      "total": 500.00
    }
  },
  "details": {
    "current": [
      {
        "id": 1,
        "invoice_number": "INV-2025-00001",
        "customer_name": "John Doe",
        "due_date": "2025-12-20",
        "days_overdue": 0,
        "currency": "USD",
        "amount": 1100.00,
        "balance": 1100.00
      }
    ],
    "1-30_days": [],
    "31-60_days": [],
    "61-90_days": [],
    "over_90_days": []
  }
}
```

---

### Profit & Loss Statement

Get a profit and loss statement for a specified date range.

**Endpoint:** `GET /api/v1/reports/profit-loss`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Query Parameters:**
- `start_date` (required, date) - Start date (YYYY-MM-DD)
- `end_date` (required, date) - End date (YYYY-MM-DD)

**Example Request:**
```
GET /api/v1/reports/profit-loss?start_date=2025-01-01&end_date=2025-12-31
```

**Success Response (200 OK):**
```json
{
  "period": {
    "start_date": "2025-01-01",
    "end_date": "2025-12-31"
  },
  "income": {
    "total_revenue": 145000.00,
    "total_payments_received": 145000.00,
    "by_currency": {
      "USD": {
        "count": 100,
        "total": 120000.00
      },
      "EUR": {
        "count": 40,
        "total": 25000.00
      }
    }
  },
  "expenses": {
    "payment_processing_fees": 2900.00,
    "sms_costs": 150.00,
    "whatsapp_costs": 200.00,
    "total_expenses": 3250.00
  },
  "net_profit": 141750.00,
  "profit_margin": 97.76
}
```

---

## Rate Limiting

All API endpoints are rate-limited based on your account settings. Default limit is **60 requests per minute**.

When you exceed the rate limit, you'll receive:

**Response (429 Too Many Requests):**
```json
{
  "message": "Rate limit exceeded. Please try again later.",
  "retry_after": 45
}
```

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
Retry-After: 45
```

---

## Error Handling

All error responses follow this format:

```json
{
  "message": "Error message here",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  }
}
```

**Common HTTP Status Codes:**
- `200 OK` - Request succeeded
- `201 Created` - Resource created successfully
- `204 No Content` - Resource deleted successfully
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Access denied
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation error
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

---

## Best Practices

1. **Secure Your Tokens:** Never expose API tokens in client-side code or public repositories
2. **Use HTTPS:** Always use HTTPS in production
3. **Handle Rate Limits:** Implement exponential backoff when rate-limited
4. **Validate Data:** Always validate data before sending to the API
5. **Error Handling:** Implement proper error handling for all API calls
6. **Pagination:** Use pagination for large datasets
7. **Token Rotation:** Regularly rotate API tokens for security

---

## Support

For API support or questions:
- **Documentation:** This file
- **Test Suite:** Run `php test-api.php` to verify API functionality
- **Settings:** Manage API access at `/app/api-settings`

---

**Last Updated:** December 12, 2025
**API Version:** v1
**Base URL:** `/api/v1`
