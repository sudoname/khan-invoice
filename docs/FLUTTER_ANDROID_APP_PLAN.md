# Khan Invoice - Flutter Android App Implementation Plan

## Overview
Build a native Android app using Flutter that connects to the existing Laravel backend via REST API.

## Architecture

```
┌─────────────────────────────────────────┐
│         Flutter Android App             │
│  ┌───────────────────────────────────┐  │
│  │  UI Layer (Screens & Widgets)     │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  State Management (Provider/Bloc) │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  Services (API, Auth, Storage)    │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  Models (Invoice, Customer, etc)  │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
                    ↕ HTTPS/JSON
┌─────────────────────────────────────────┐
│      Laravel Backend (Existing)         │
│  ┌───────────────────────────────────┐  │
│  │  REST API (/api/v1/...)           │  │
│  │  - Sanctum Authentication         │  │
│  │  - Invoice CRUD                   │  │
│  │  - Customer Management            │  │
│  │  - Payment Processing             │  │
│  │  - Reports                        │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

---

## Prerequisites

### 1. Backend (Laravel) - Week 4 API Implementation
**Must complete first** - This is already designed in your main plan!

From your existing plan:
- ✅ Install Sanctum
- ✅ Create API routes (/api/v1/...)
- ✅ Build controllers (AuthController, InvoiceController, etc.)
- ✅ Create API Resources (InvoiceResource, CustomerResource)
- ✅ Implement rate limiting

**Estimated Time:** 2 weeks (as per your original Week 4 plan)

### 2. Flutter Development Environment Setup
```bash
# Install Flutter SDK
# Download from: https://docs.flutter.dev/get-started/install/windows

# Install Android Studio
# Download from: https://developer.android.com/studio

# Verify installation
flutter doctor

# Expected output:
# ✓ Flutter (Channel stable)
# ✓ Android toolchain
# ✓ Android Studio
```

**Estimated Time:** 1 day

---

## Implementation Phases

## Phase 1: Backend API (2 weeks)

### Complete your existing Week 4 plan:

**Files to create:**
```
routes/api.php
app/Http/Controllers/Api/V1/AuthController.php
app/Http/Controllers/Api/V1/InvoiceController.php
app/Http/Controllers/Api/V1/CustomerController.php
app/Http/Controllers/Api/V1/PaymentController.php
app/Http/Controllers/Api/V1/ReportController.php
app/Http/Resources/InvoiceResource.php
app/Http/Resources/CustomerResource.php
app/Http/Resources/PaymentResource.php
app/Http/Middleware/ApiRateLimit.php
```

**API Endpoints needed:**
```
POST   /api/v1/auth/login              - Get token
POST   /api/v1/auth/logout             - Revoke token
GET    /api/v1/auth/user               - Get current user

GET    /api/v1/invoices                - List invoices
POST   /api/v1/invoices                - Create invoice
GET    /api/v1/invoices/{id}           - View invoice
PUT    /api/v1/invoices/{id}           - Update invoice
DELETE /api/v1/invoices/{id}           - Delete invoice
POST   /api/v1/invoices/{id}/send      - Send invoice
GET    /api/v1/invoices/{id}/pdf       - Download PDF

GET    /api/v1/customers               - List customers
POST   /api/v1/customers               - Create customer
GET    /api/v1/customers/{id}          - View customer
PUT    /api/v1/customers/{id}          - Update customer
DELETE /api/v1/customers/{id}          - Delete customer

GET    /api/v1/payments                - List payments
GET    /api/v1/payments/{id}           - View payment

GET    /api/v1/dashboard               - Dashboard stats
GET    /api/v1/reports/sales           - Sales report
GET    /api/v1/reports/aging           - Aging report
GET    /api/v1/reports/profit-loss     - P&L report

GET    /api/v1/subscription            - Current subscription
GET    /api/v1/plans                   - Available plans
```

**Testing with curl:**
```bash
# Get token
curl -X POST https://staging.kinvoice.ng/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Response: {"token": "1|abc123...", "user": {...}}

# Use token
curl -H "Authorization: Bearer 1|abc123..." \
  https://staging.kinvoice.ng/api/v1/invoices
```

---

## Phase 2: Flutter App Foundation (1 week)

### Create Flutter project:
```bash
flutter create khan_invoice_app
cd khan_invoice_app
```

### Project Structure:
```
khan_invoice_app/
├── lib/
│   ├── main.dart
│   ├── config/
│   │   └── api_config.dart           # API base URLs
│   ├── models/
│   │   ├── user.dart
│   │   ├── invoice.dart
│   │   ├── customer.dart
│   │   ├── payment.dart
│   │   └── subscription.dart
│   ├── services/
│   │   ├── api_service.dart          # HTTP client wrapper
│   │   ├── auth_service.dart         # Login/logout
│   │   ├── storage_service.dart      # Local storage (token)
│   │   ├── invoice_service.dart
│   │   └── customer_service.dart
│   ├── providers/
│   │   ├── auth_provider.dart
│   │   ├── invoice_provider.dart
│   │   └── customer_provider.dart
│   ├── screens/
│   │   ├── auth/
│   │   │   ├── login_screen.dart
│   │   │   └── register_screen.dart
│   │   ├── dashboard/
│   │   │   └── dashboard_screen.dart
│   │   ├── invoices/
│   │   │   ├── invoice_list_screen.dart
│   │   │   ├── invoice_detail_screen.dart
│   │   │   └── invoice_form_screen.dart
│   │   ├── customers/
│   │   │   ├── customer_list_screen.dart
│   │   │   └── customer_form_screen.dart
│   │   └── reports/
│   │       └── reports_screen.dart
│   ├── widgets/
│   │   ├── custom_app_bar.dart
│   │   ├── invoice_card.dart
│   │   └── loading_indicator.dart
│   └── utils/
│       ├── constants.dart
│       └── validators.dart
└── pubspec.yaml
```

### Dependencies (pubspec.yaml):
```yaml
dependencies:
  flutter:
    sdk: flutter

  # HTTP & API
  http: ^1.2.0
  dio: ^5.4.0                    # Advanced HTTP client

  # State Management
  provider: ^6.1.0               # Simple & recommended

  # Storage
  shared_preferences: ^2.2.0     # Store auth token

  # UI Components
  google_fonts: ^6.1.0
  flutter_svg: ^2.0.0
  cached_network_image: ^3.3.0

  # Forms
  flutter_form_builder: ^9.1.0

  # PDF Viewing
  flutter_pdfview: ^1.3.0

  # Date handling
  intl: ^0.19.0

  # Loading indicators
  flutter_spinkit: ^5.2.0
```

### Core Files:

#### 1. `lib/config/api_config.dart`
```dart
class ApiConfig {
  static const String baseUrl = 'https://staging.kinvoice.ng/api/v1';
  static const String storageKey = 'auth_token';

  // Endpoints
  static const String login = '$baseUrl/auth/login';
  static const String logout = '$baseUrl/auth/logout';
  static const String invoices = '$baseUrl/invoices';
  static const String customers = '$baseUrl/customers';
  static const String payments = '$baseUrl/payments';
  static const String dashboard = '$baseUrl/dashboard';
}
```

#### 2. `lib/services/api_service.dart`
```dart
import 'package:dio/dio.dart';
import 'storage_service.dart';

class ApiService {
  static final Dio _dio = Dio(BaseOptions(
    baseUrl: 'https://staging.kinvoice.ng/api/v1',
    connectTimeout: Duration(seconds: 30),
    receiveTimeout: Duration(seconds: 30),
    headers: {'Content-Type': 'application/json'},
  ));

  static Future<void> init() async {
    // Add auth token interceptor
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await StorageService.getToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) {
          // Token expired - logout user
          StorageService.clearToken();
        }
        return handler.next(error);
      },
    ));
  }

  static Dio get dio => _dio;
}
```

#### 3. `lib/services/auth_service.dart`
```dart
import 'api_service.dart';
import 'storage_service.dart';
import '../models/user.dart';

class AuthService {
  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await ApiService.dio.post('/auth/login', data: {
        'email': email,
        'password': password,
      });

      if (response.statusCode == 200) {
        final token = response.data['token'];
        final user = User.fromJson(response.data['user']);

        await StorageService.saveToken(token);

        return {'success': true, 'user': user};
      }
      return {'success': false, 'message': 'Invalid credentials'};
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  Future<void> logout() async {
    try {
      await ApiService.dio.post('/auth/logout');
    } finally {
      await StorageService.clearToken();
    }
  }

  Future<bool> isLoggedIn() async {
    final token = await StorageService.getToken();
    return token != null;
  }
}
```

#### 4. `lib/models/invoice.dart`
```dart
class Invoice {
  final int id;
  final String invoiceNumber;
  final int customerId;
  final String customerName;
  final double totalAmount;
  final double amountPaid;
  final String status;
  final DateTime? dueDate;
  final DateTime createdAt;

  Invoice({
    required this.id,
    required this.invoiceNumber,
    required this.customerId,
    required this.customerName,
    required this.totalAmount,
    required this.amountPaid,
    required this.status,
    this.dueDate,
    required this.createdAt,
  });

  factory Invoice.fromJson(Map<String, dynamic> json) {
    return Invoice(
      id: json['id'],
      invoiceNumber: json['invoice_number'],
      customerId: json['customer_id'],
      customerName: json['customer']['name'],
      totalAmount: double.parse(json['total_amount'].toString()),
      amountPaid: double.parse(json['amount_paid'].toString()),
      status: json['status'],
      dueDate: json['due_date'] != null ? DateTime.parse(json['due_date']) : null,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  String get formattedTotal => '₦${totalAmount.toStringAsFixed(2)}';
  double get amountDue => totalAmount - amountPaid;
  bool get isPaid => status == 'paid';
  bool get isOverdue => dueDate != null &&
                        DateTime.now().isAfter(dueDate!) &&
                        !isPaid;
}
```

#### 5. `lib/screens/auth/login_screen.dart`
```dart
import 'package:flutter/material.dart';
import '../../services/auth_service.dart';
import '../dashboard/dashboard_screen.dart';

class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _authService = AuthService();
  bool _isLoading = false;

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final result = await _authService.login(
      _emailController.text,
      _passwordController.text,
    );

    setState(() => _isLoading = false);

    if (result['success']) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => DashboardScreen()),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'])),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Logo
                Text(
                  'Khan Invoice',
                  style: TextStyle(fontSize: 32, fontWeight: FontWeight.bold),
                ),
                SizedBox(height: 48),

                // Email field
                TextFormField(
                  controller: _emailController,
                  decoration: InputDecoration(
                    labelText: 'Email',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.email),
                  ),
                  keyboardType: TextInputType.emailAddress,
                  validator: (value) {
                    if (value?.isEmpty ?? true) return 'Email required';
                    if (!value!.contains('@')) return 'Invalid email';
                    return null;
                  },
                ),
                SizedBox(height: 16),

                // Password field
                TextFormField(
                  controller: _passwordController,
                  decoration: InputDecoration(
                    labelText: 'Password',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.lock),
                  ),
                  obscureText: true,
                  validator: (value) {
                    if (value?.isEmpty ?? true) return 'Password required';
                    return null;
                  },
                ),
                SizedBox(height: 24),

                // Login button
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _login,
                    child: _isLoading
                        ? CircularProgressIndicator(color: Colors.white)
                        : Text('Login'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
```

---

## Phase 3: Core Features (4-5 weeks)

### Week 1: Dashboard & Navigation
- Bottom navigation bar (Dashboard, Invoices, Customers, Reports, Profile)
- Dashboard with stats cards (total invoices, paid, pending, overdue)
- Recent invoices list
- Quick action buttons

### Week 2: Invoice Management
- Invoice list screen (with search, filters)
- Invoice detail screen (view full invoice)
- Create/edit invoice form
- Send invoice (email/SMS)
- Download PDF
- Mark as paid

### Week 3: Customer Management
- Customer list screen
- Customer detail screen (with invoice history)
- Create/edit customer form
- Delete customer

### Week 4: Reports & Additional Features
- Sales report
- Aging report
- Profit & Loss report
- Payment history
- Subscription management

### Week 5: Polish & Testing
- Error handling
- Offline mode indicators
- Loading states
- Pull-to-refresh
- Empty states
- Validation messages

---

## Phase 4: Build & Deploy (1 week)

### Build APK:
```bash
# Build release APK
flutter build apk --release

# APK location:
# build/app/outputs/flutter-apk/app-release.apk
```

### Test on Device:
```bash
# Install on connected Android device
flutter install
```

### Google Play Store (Optional):
```bash
# Build App Bundle for Play Store
flutter build appbundle --release

# Bundle location:
# build/app/outputs/bundle/release/app-release.aab
```

---

## Timeline Summary

| Phase | Task | Duration |
|-------|------|----------|
| 1 | Backend API (Week 4 from main plan) | 2 weeks |
| 2 | Flutter Foundation | 1 week |
| 3 | Core Features | 4-5 weeks |
| 4 | Build & Deploy | 1 week |
| **Total** | | **8-9 weeks** |

---

## Development Workflow

### Daily Workflow:
```bash
# 1. Start Laravel backend
cd C:\Users\yomi\khan-invoice
php artisan serve

# 2. Run Flutter app
cd C:\Users\yomi\khan_invoice_app
flutter run

# 3. Hot reload on save (Ctrl + S in VS Code)
# Changes appear instantly on device/emulator
```

### Testing:
```bash
# Test API with Postman first
# Then test in Flutter app

# Check logs:
flutter logs
```

---

## Key Considerations

### 1. Authentication Flow
```
App Launch → Check token → Valid? → Dashboard
                          ↓ Invalid
                      Login Screen
```

### 2. Offline Handling
- Show cached data with "Offline" indicator
- Queue actions (create invoice) for when online
- Use `connectivity_plus` package to detect network

### 3. Multi-tenancy
- All API requests automatically filtered by authenticated user
- No risk of seeing other users' data (handled by Laravel)

### 4. Payment Integration
- Paystack Flutter SDK available: `flutter_paystack`
- Can process payments directly in app
- Or redirect to web payment page

### 5. File Handling
- PDF viewing: `flutter_pdfview`
- PDF download: `path_provider` + `permission_handler`
- Share PDF: `share_plus`

---

## Cost Breakdown

### Development:
- **DIY:** Free (just your time)
- **Hire Flutter dev:** $2,000 - $5,000 (8-9 weeks at $250-500/week)

### Deployment:
- **Google Play Store:** $25 one-time registration fee
- **App signing:** Free (handled by Play Console)

### Hosting:
- **No change** - uses existing Laravel backend

---

## Next Steps

### Immediate (This Week):
1. ✅ Complete API implementation (Week 4 of your main plan)
2. ✅ Test all API endpoints with Postman/curl
3. ✅ Document API responses

### Week 2:
1. Install Flutter & Android Studio
2. Create Flutter project
3. Set up authentication flow
4. Build login screen

### Week 3+:
1. Build core screens (Dashboard, Invoices, Customers)
2. Integrate with API
3. Test on real Android device

---

## Resources

### Learning Flutter:
- Official docs: https://docs.flutter.dev
- Flutter cookbook: https://docs.flutter.dev/cookbook
- Dart language tour: https://dart.dev/guides/language/language-tour

### UI Design:
- Material Design 3: https://m3.material.io
- Flutter widgets catalog: https://docs.flutter.dev/ui/widgets

### Community:
- Flutter Discord: https://discord.gg/flutter
- r/FlutterDev subreddit
- Stack Overflow

---

## Decision Point: NOW

Before we start, confirm:

1. ✅ You want Android-only (not iOS)?
2. ✅ You'll complete the API first (2 weeks)?
3. ✅ You have time to learn Flutter, or will hire a dev?
4. ✅ You have an Android device for testing?

**Should we start with Phase 1 (Backend API implementation) now?**
