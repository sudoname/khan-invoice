<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Khan Invoice - Nigerian Invoice Management System' }}</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="{{ $description ?? 'Create professional invoices in minutes with Khan Invoice. Built-in VAT (7.5%), WHT, Paystack payments, and WhatsApp sharing for Nigerian businesses.' }}">
    <meta name="keywords" content="invoice generator Nigeria, Nigerian invoice software, VAT invoice, WHT invoice, Naira invoice, Paystack invoice, invoice management Nigeria">
    <meta name="author" content="Khan Invoice">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- OpenGraph Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Khan Invoice">
    <meta property="og:title" content="{{ $ogTitle ?? $title ?? 'Khan Invoice - Nigerian Invoice Management System' }}">
    <meta property="og:description" content="{{ $ogDescription ?? $description ?? 'Create professional invoices in minutes with Khan Invoice. Built-in VAT (7.5%), WHT, Paystack payments, and WhatsApp sharing for Nigerian businesses.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $twitterTitle ?? $title ?? 'Khan Invoice - Nigerian Invoice Management System' }}">
    <meta name="twitter:description" content="{{ $twitterDescription ?? $description ?? 'Create professional invoices in minutes with Khan Invoice. Built-in VAT (7.5%), WHT, Paystack payments, and WhatsApp sharing for Nigerian businesses.' }}">
    <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

    <!-- Additional Meta -->
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#667eea">

    <!-- Google AdSense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8164278094998913"
     crossorigin="anonymous"></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="ml-2 text-xl font-bold gradient-text">Khan Invoice</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/about" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium">About</a>
                    <a href="{{ route('pricing') }}" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium">Pricing</a>
                    <a href="/faq" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium">FAQ</a>
                    <a href="{{ route('public-invoice.create') }}" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium">Generate Invoice</a>
                    <a href="/contact" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium">Contact</a>
                    @auth
                        <a href="/app" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition">Dashboard</a>
                    @else
                        <a href="/login" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition">Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="ml-2 text-xl font-bold text-white">Khan Invoice</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Professional Nigerian invoice management system with VAT, WHT, and NGN support.
                        Streamline your billing process today.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-white font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="/about" class="hover:text-purple-400 transition">About Us</a></li>
                        <li><a href="/contact" class="hover:text-purple-400 transition">Contact Us</a></li>
                        <li><a href="/admin" class="hover:text-purple-400 transition">Dashboard</a></li>
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h3 class="text-white font-semibold mb-4">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="/policy/privacy" class="hover:text-purple-400 transition">Privacy Policy</a></li>
                        <li><a href="/policy/terms" class="hover:text-purple-400 transition">Terms of Service</a></li>
                        <li><a href="/auth/facebook/deletion" class="hover:text-purple-400 transition">Data Deletion</a></li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-500">
                <p>&copy; {{ date('Y') }} Khan Invoice. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Analytics -->
    <script src="{{ asset('js/analytics.js') }}"></script>
</body>
</html>
