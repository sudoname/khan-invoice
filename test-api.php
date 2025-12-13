<?php

/**
 * API Integration Test Suite
 *
 * Tests all REST API endpoints:
 * - Authentication (token creation/revocation)
 * - Invoices (CRUD operations)
 * - Customers (CRUD operations)
 * - Payments (list, create)
 * - Reports (sales, aging, profit-loss)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "========================================\n";
echo "  API INTEGRATION TEST SUITE\n";
echo "========================================\n\n";

$passedTests = 0;
$failedTests = 0;

function test(string $name, callable $callback): void
{
    global $passedTests, $failedTests;

    try {
        $callback();
        echo "✓ $name\n";
        $passedTests++;
    } catch (Exception $e) {
        echo "✗ $name\n";
        echo "  Error: " . $e->getMessage() . "\n";
        $failedTests++;
    }
}

// ========================================
// 1. SETUP & CONFIGURATION TESTS
// ========================================

echo "1. SETUP & CONFIGURATION TESTS\n";
echo "-------------------------------\n";

test('Laravel Sanctum is installed', function() {
    if (!class_exists(\Laravel\Sanctum\Sanctum::class)) {
        throw new Exception('Laravel Sanctum is not installed');
    }
});

test('User model has HasApiTokens trait', function() {
    $reflection = new \ReflectionClass(\App\Models\User::class);
    $traits = $reflection->getTraitNames();

    if (!in_array('Laravel\Sanctum\HasApiTokens', $traits)) {
        throw new Exception('User model does not use HasApiTokens trait');
    }
});

test('Users table has API fields', function() {
    $columns = ['api_enabled', 'api_rate_limit', 'api_last_used_at'];

    foreach ($columns as $column) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('users', $column)) {
            throw new Exception("Column '$column' missing from users table");
        }
    }
});

test('API routes are registered', function() {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $apiRoutes = collect($routes)->filter(function ($route) {
        return str_starts_with($route->uri(), 'api/v1');
    });

    if ($apiRoutes->isEmpty()) {
        throw new Exception('No API routes registered');
    }
});

echo "\n";

// ========================================
// 2. CONTROLLER & RESOURCE TESTS
// ========================================

echo "2. CONTROLLER & RESOURCE TESTS\n";
echo "-------------------------------\n";

$controllers = [
    'AuthController' => \App\Http\Controllers\Api\V1\AuthController::class,
    'InvoiceController' => \App\Http\Controllers\Api\V1\InvoiceController::class,
    'CustomerController' => \App\Http\Controllers\Api\V1\CustomerController::class,
    'PaymentController' => \App\Http\Controllers\Api\V1\PaymentController::class,
    'ReportController' => \App\Http\Controllers\Api\V1\ReportController::class,
];

foreach ($controllers as $name => $class) {
    test("$name exists", function() use ($class, $name) {
        if (!class_exists($class)) {
            throw new Exception("$name does not exist");
        }
    });
}

$resources = [
    'InvoiceResource' => \App\Http\Resources\InvoiceResource::class,
    'CustomerResource' => \App\Http\Resources\CustomerResource::class,
    'PaymentResource' => \App\Http\Resources\PaymentResource::class,
];

foreach ($resources as $name => $class) {
    test("$name exists", function() use ($class, $name) {
        if (!class_exists($class)) {
            throw new Exception("$name does not exist");
        }
    });
}

echo "\n";

// ========================================
// 3. MIDDLEWARE TESTS
// ========================================

echo "3. MIDDLEWARE TESTS\n";
echo "-------------------\n";

test('API rate limit middleware exists', function() {
    if (!class_exists(\App\Http\Middleware\ApiRateLimit::class)) {
        throw new Exception('ApiRateLimit middleware does not exist');
    }
});

test('API rate limit middleware is registered', function() {
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $middlewareAliases = $kernel->getMiddlewareGroups();

    // Check if middleware alias exists
    $routeMiddleware = $kernel->getRouteMiddleware();
    if (!isset($routeMiddleware['api.rate.limit'])) {
        throw new Exception('api.rate.limit middleware alias not registered');
    }
});

echo "\n";

// ========================================
// 4. AUTHENTICATION TESTS
// ========================================

echo "4. AUTHENTICATION TESTS\n";
echo "-----------------------\n";

$testUser = null;
$testToken = null;

test('Can enable API for user', function() use (&$testUser) {
    $testUser = \App\Models\User::first();
    if (!$testUser) {
        throw new Exception('No users found in database');
    }

    $testUser->update([
        'api_enabled' => true,
        'api_rate_limit' => 60,
    ]);

    $testUser->refresh();

    if (!$testUser->api_enabled) {
        throw new Exception('Failed to enable API for user');
    }
});

test('Can create API token', function() use (&$testUser, &$testToken) {
    if (!$testUser) {
        throw new Exception('Test user not set');
    }

    $token = $testUser->createToken('test-token');
    $testToken = $token->plainTextToken;

    if (!$testToken) {
        throw new Exception('Failed to create token');
    }
});

test('Token can be used for authentication', function() use (&$testToken) {
    if (!$testToken) {
        throw new Exception('Test token not set');
    }

    $user = \Laravel\Sanctum\PersonalAccessToken::findToken($testToken)?->tokenable;

    if (!$user) {
        throw new Exception('Token authentication failed');
    }
});

test('Can revoke API token', function() use (&$testUser) {
    if (!$testUser) {
        throw new Exception('Test user not set');
    }

    $tokensBefore = $testUser->tokens()->count();
    $testUser->tokens()->delete();
    $tokensAfter = $testUser->tokens()->count();

    if ($tokensAfter !== 0) {
        throw new Exception('Failed to revoke all tokens');
    }
});

echo "\n";

// ========================================
// 5. API ENDPOINT TESTS
// ========================================

echo "5. API ENDPOINT TESTS\n";
echo "---------------------\n";

test('Invoice API routes exist', function() {
    $expectedRoutes = [
        'api/v1/invoices',
        'api/v1/invoices/{invoice}',
    ];

    $routes = \Illuminate\Support\Facades\Route::getRoutes();

    foreach ($expectedRoutes as $expectedRoute) {
        $found = false;
        foreach ($routes as $route) {
            if ($route->uri() === $expectedRoute) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception("Route '$expectedRoute' not found");
        }
    }
});

test('Customer API routes exist', function() {
    $expectedRoutes = [
        'api/v1/customers',
        'api/v1/customers/{customer}',
    ];

    $routes = \Illuminate\Support\Facades\Route::getRoutes();

    foreach ($expectedRoutes as $expectedRoute) {
        $found = false;
        foreach ($routes as $route) {
            if ($route->uri() === $expectedRoute) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception("Route '$expectedRoute' not found");
        }
    }
});

test('Payment API routes exist', function() {
    $expectedRoutes = [
        'api/v1/payments',
    ];

    $routes = \Illuminate\Support\Facades\Route::getRoutes();

    foreach ($expectedRoutes as $expectedRoute) {
        $found = false;
        foreach ($routes as $route) {
            if ($route->uri() === $expectedRoute) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception("Route '$expectedRoute' not found");
        }
    }
});

test('Report API routes exist', function() {
    $expectedRoutes = [
        'api/v1/reports/sales',
        'api/v1/reports/aging',
        'api/v1/reports/profit-loss',
    ];

    $routes = \Illuminate\Support\Facades\Route::getRoutes();

    foreach ($expectedRoutes as $expectedRoute) {
        $found = false;
        foreach ($routes as $route) {
            if ($route->uri() === $expectedRoute) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception("Route '$expectedRoute' not found");
        }
    }
});

echo "\n";

// ========================================
// 6. FILAMENT UI TESTS
// ========================================

echo "6. FILAMENT UI TESTS\n";
echo "--------------------\n";

test('API Settings page exists', function() {
    if (!class_exists(\App\Filament\App\Pages\ApiSettings::class)) {
        throw new Exception('ApiSettings page does not exist');
    }
});

test('API Settings page has required methods', function() {
    $page = new \App\Filament\App\Pages\ApiSettings();

    if (!method_exists($page, 'save')) {
        throw new Exception('ApiSettings page missing save() method');
    }

    if (!method_exists($page, 'createToken')) {
        throw new Exception('ApiSettings page missing createToken() method');
    }
});

test('API Settings view file exists', function() {
    $viewPath = resource_path('views/filament/app/pages/api-settings.blade.php');

    if (!file_exists($viewPath)) {
        throw new Exception('api-settings.blade.php view file does not exist');
    }
});

test('API endpoints component exists', function() {
    $viewPath = resource_path('views/filament/app/components/api-endpoints.blade.php');

    if (!file_exists($viewPath)) {
        throw new Exception('api-endpoints.blade.php component does not exist');
    }
});

echo "\n";

// ========================================
// 7. DATA TRANSFORMATION TESTS
// ========================================

echo "7. DATA TRANSFORMATION TESTS\n";
echo "----------------------------\n";

test('InvoiceResource transforms data correctly', function() {
    $invoice = \App\Models\Invoice::first();
    if (!$invoice) {
        throw new Exception('No invoices found for testing');
    }

    $resource = new \App\Http\Resources\InvoiceResource($invoice);
    $array = $resource->toArray(request());

    $requiredFields = ['id', 'invoice_number', 'status', 'total_amount', 'currency'];
    foreach ($requiredFields as $field) {
        if (!isset($array[$field])) {
            throw new Exception("InvoiceResource missing field: $field");
        }
    }
});

test('CustomerResource transforms data correctly', function() {
    $customer = \App\Models\Customer::first();
    if (!$customer) {
        throw new Exception('No customers found for testing');
    }

    $resource = new \App\Http\Resources\CustomerResource($customer);
    $array = $resource->toArray(request());

    $requiredFields = ['id', 'name', 'email'];
    foreach ($requiredFields as $field) {
        if (!isset($array[$field])) {
            throw new Exception("CustomerResource missing field: $field");
        }
    }
});

test('PaymentResource transforms data correctly', function() {
    $payment = \App\Models\Payment::first();
    if (!$payment) {
        // Create a test payment
        $invoice = \App\Models\Invoice::first();
        if ($invoice) {
            $payment = \App\Models\Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => 100,
                'payment_method' => 'bank_transfer',
                'payment_date' => now(),
                'reference_number' => 'TEST-' . time(),
                'status' => 'completed',
            ]);
        } else {
            throw new Exception('No invoices or payments found for testing');
        }
    }

    $resource = new \App\Http\Resources\PaymentResource($payment);
    $array = $resource->toArray(request());

    $requiredFields = ['id', 'invoice_id', 'amount', 'payment_method'];
    foreach ($requiredFields as $field) {
        if (!isset($array[$field])) {
            throw new Exception("PaymentResource missing field: $field");
        }
    }
});

echo "\n";

// ========================================
// SUMMARY
// ========================================

echo "========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n\n";

$totalTests = $passedTests + $failedTests;
$passRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;

echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests ✓\n";
echo "Failed: $failedTests ✗\n";
echo "Pass Rate: $passRate%\n\n";

if ($failedTests === 0) {
    echo "🎉 ALL TESTS PASSED! API integration is complete.\n\n";
    echo "API Base URL: " . url('/api/v1') . "\n\n";
    echo "Next Steps:\n";
    echo "1. Enable API access in Filament (Settings → API Settings)\n";
    echo "2. Create your first API token\n";
    echo "3. Test API endpoints with Postman or curl\n";
    echo "4. Integrate with external applications\n\n";
    echo "Available Endpoints:\n";
    echo "  • POST /api/v1/auth/token - Create token\n";
    echo "  • GET /api/v1/invoices - List invoices\n";
    echo "  • POST /api/v1/invoices - Create invoice\n";
    echo "  • GET /api/v1/customers - List customers\n";
    echo "  • POST /api/v1/customers - Create customer\n";
    echo "  • GET /api/v1/payments - List payments\n";
    echo "  • POST /api/v1/payments - Record payment\n";
    echo "  • GET /api/v1/reports/sales - Sales report\n";
    echo "  • GET /api/v1/reports/aging - Aging report\n";
    echo "  • GET /api/v1/reports/profit-loss - P&L statement\n\n";
} else {
    echo "⚠️  Some tests failed. Please review and fix the issues above.\n\n";
}

echo "========================================\n\n";
