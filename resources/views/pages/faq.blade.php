<x-layout>
    <x-slot name="title">FAQ - Khan Invoice</x-slot>

    <!-- Hero Section -->
    <div class="gradient-bg text-white py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Frequently Asked Questions</h1>
            <p class="text-xl text-purple-100">
                Everything you need to know about using Khan Invoice
            </p>
        </div>
    </div>

    <!-- FAQ Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Getting Started -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-8 h-8 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Getting Started
            </h2>

            <div class="space-y-6">
                <!-- Question 1 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-purple-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">How do I create an account?</h3>
                    <p class="text-gray-700 mb-2">
                        Click the "Login" button in the top navigation, then select "Sign up" or "Register". You can create an account using:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 ml-4 space-y-1">
                        <li>Email and password</li>
                        <li>Google account (OAuth)</li>
                        <li>Facebook account (OAuth)</li>
                    </ul>
                    <p class="text-gray-700 mt-2">
                        After registration, you'll need to verify your email address before accessing the dashboard.
                    </p>
                </div>

                <!-- Question 2 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-purple-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Is Khan Invoice free to use?</h3>
                    <p class="text-gray-700">
                        Yes! Khan Invoice is currently free to use. You can create unlimited invoices, manage multiple business profiles,
                        track payments, and access all features at no cost. We may introduce premium features in the future, but the core
                        invoicing functionality will always remain free.
                    </p>
                </div>

                <!-- Question 3 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-purple-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Do I need to download any software?</h3>
                    <p class="text-gray-700">
                        No! Khan Invoice is a web-based application that works in your browser. Simply visit
                        <a href="https://kinvoice.ng" class="text-purple-600 hover:underline font-semibold">kinvoice.ng</a>
                        and log in to access your account from any device - desktop, tablet, or mobile.
                    </p>
                </div>
            </div>
        </div>

        <!-- Business Profiles -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Business Profiles
            </h2>

            <div class="space-y-6">
                <!-- Question 4 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-blue-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">What is a Business Profile?</h3>
                    <p class="text-gray-700 mb-2">
                        A Business Profile contains your company information that appears on invoices. Each profile includes:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 ml-4 space-y-1">
                        <li>Business name and logo</li>
                        <li>Contact information (email, phone, address)</li>
                        <li>Bank account details for payments</li>
                        <li>Tax identification numbers</li>
                        <li>Paystack integration for online payments</li>
                    </ul>
                </div>

                <!-- Question 5 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-blue-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Can I have multiple Business Profiles?</h3>
                    <p class="text-gray-700">
                        Yes! You can create multiple Business Profiles under one account. This is perfect if you run multiple businesses
                        or operate under different business names. Each profile can have its own branding, bank details, and invoicing settings.
                    </p>
                </div>

                <!-- Question 6 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-blue-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">How do I add my business logo?</h3>
                    <p class="text-gray-700">
                        Go to <strong>Business Profiles</strong> in your dashboard, edit your profile, and upload your logo image.
                        Supported formats are PNG, JPG, and JPEG. Your logo will automatically appear on all invoices created under that profile.
                    </p>
                </div>
            </div>
        </div>

        <!-- Creating Invoices -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Creating Invoices
            </h2>

            <div class="space-y-6">
                <!-- Question 7 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-green-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">How do I create an invoice?</h3>
                    <p class="text-gray-700 mb-2">Follow these simple steps:</p>
                    <ol class="list-decimal list-inside text-gray-700 ml-4 space-y-1">
                        <li>Go to <strong>Invoices</strong> in your dashboard</li>
                        <li>Click <strong>"New Invoice"</strong></li>
                        <li>Select your Business Profile</li>
                        <li>Choose or add a Customer</li>
                        <li>Add invoice items (description, quantity, price)</li>
                        <li>Set payment terms and due date</li>
                        <li>Add VAT (7.5%) or WHT if applicable</li>
                        <li>Click <strong>"Create"</strong></li>
                    </ol>
                </div>

                <!-- Question 8 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-green-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Can I send invoices to my customers?</h3>
                    <p class="text-gray-700 mb-2">
                        Yes! You have multiple options to share invoices:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 ml-4 space-y-1">
                        <li><strong>Email:</strong> Send directly from the system</li>
                        <li><strong>WhatsApp:</strong> Share invoice link via WhatsApp</li>
                        <li><strong>Public Link:</strong> Copy and share the unique invoice URL</li>
                        <li><strong>PDF Download:</strong> Download and send manually</li>
                    </ul>
                </div>

                <!-- Question 9 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-green-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">What Nigerian tax features are supported?</h3>
                    <p class="text-gray-700 mb-2">
                        Khan Invoice is built specifically for Nigerian businesses and supports:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 ml-4 space-y-1">
                        <li><strong>VAT:</strong> 7.5% Value Added Tax</li>
                        <li><strong>WHT:</strong> Withholding Tax (customizable rates)</li>
                        <li><strong>NGN Currency:</strong> All amounts in Nigerian Naira (₦)</li>
                        <li><strong>Nigerian Date Format:</strong> DD/MM/YYYY</li>
                    </ul>
                </div>

                <!-- Question 10 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-green-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Can I edit an invoice after creating it?</h3>
                    <p class="text-gray-700">
                        Yes, you can edit invoices that are still in "Draft" or "Sent" status. Once an invoice is marked as "Paid"
                        or "Partially Paid", editing is restricted to maintain payment accuracy. You can void or cancel paid invoices
                        if needed.
                    </p>
                </div>
            </div>
        </div>

        <!-- Payments -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-8 h-8 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                Payments & Paystack
            </h2>

            <div class="space-y-6">
                <!-- Question 11 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-yellow-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">What is Paystack integration?</h3>
                    <p class="text-gray-700">
                        Paystack is a Nigerian payment gateway that allows your customers to pay invoices online using
                        debit cards, bank transfers, USSD, or mobile money. When you integrate Paystack, a "Pay Now" button
                        appears on your invoices, making it easy for customers to pay instantly.
                    </p>
                </div>

                <!-- Question 12 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-yellow-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">How do I receive payments directly to my bank account?</h3>
                    <p class="text-gray-700 mb-2">
                        Khan Invoice supports <strong>Paystack Subaccounts</strong>, which routes payments directly to your bank account:
                    </p>
                    <ol class="list-decimal list-inside text-gray-700 ml-4 space-y-1">
                        <li>Add your bank account details in your Business Profile</li>
                        <li>Contact support to set up your Paystack subaccount</li>
                        <li>Once configured, all invoice payments go directly to your bank</li>
                        <li>Funds are settled by Paystack (usually T+1 or T+2 days)</li>
                    </ol>
                </div>

                <!-- Question 13 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-yellow-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Can I accept partial payments?</h3>
                    <p class="text-gray-700">
                        Yes! Khan Invoice supports partial payments. If a customer pays less than the full invoice amount,
                        the invoice status changes to "Partially Paid" and shows the remaining balance. Customers can make
                        multiple payments until the invoice is fully paid.
                    </p>
                </div>

                <!-- Question 14 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-yellow-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">How do I track payments?</h3>
                    <p class="text-gray-700 mb-2">
                        Payment tracking is easy:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 ml-4 space-y-1">
                        <li><strong>Dashboard:</strong> View payment statistics and trends</li>
                        <li><strong>Invoices:</strong> Each invoice shows payment status and history</li>
                        <li><strong>Reports:</strong> Generate sales reports and profit/loss statements</li>
                        <li><strong>Notifications:</strong> Receive email alerts when payments are received</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Customers -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-8 h-8 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Customers
            </h2>

            <div class="space-y-6">
                <!-- Question 15 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-red-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">How do I manage customers?</h3>
                    <p class="text-gray-700">
                        Go to <strong>Customers</strong> in your dashboard to add, edit, or view customer information.
                        Save customer details like name, email, phone, address, and tax ID for quick invoice creation.
                        You can also see each customer's total invoiced amount and payment history.
                    </p>
                </div>

                <!-- Question 16 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-red-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Can customers view their invoices without logging in?</h3>
                    <p class="text-gray-700">
                        Yes! Each invoice has a unique public URL that customers can access without creating an account.
                        They can view the invoice details, download PDF, and make payments directly from this page.
                    </p>
                </div>
            </div>
        </div>

        <!-- Reports & Analytics -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Reports & Analytics
            </h2>

            <div class="space-y-6">
                <!-- Question 17 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-indigo-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">What reports are available?</h3>
                    <p class="text-gray-700 mb-2">Khan Invoice provides comprehensive business reports:</p>
                    <ul class="list-disc list-inside text-gray-700 ml-4 space-y-1">
                        <li><strong>Sales Report:</strong> Monthly breakdown of invoices and revenue</li>
                        <li><strong>Profit & Loss Statement:</strong> Income vs expenses analysis</li>
                        <li><strong>Aging Report:</strong> Track overdue invoices and payment delays</li>
                        <li><strong>All Transactions:</strong> Complete payment history</li>
                        <li><strong>Dashboard Statistics:</strong> Real-time metrics and growth trends</li>
                    </ul>
                </div>

                <!-- Question 18 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-indigo-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Can I export reports?</h3>
                    <p class="text-gray-700">
                        Yes! Most reports can be exported to PDF or Excel formats for printing, sharing with your accountant,
                        or importing into other accounting software.
                    </p>
                </div>
            </div>
        </div>

        <!-- Technical & Security -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-8 h-8 text-pink-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Security & Privacy
            </h2>

            <div class="space-y-6">
                <!-- Question 19 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-pink-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Is my data secure?</h3>
                    <p class="text-gray-700 mb-2">
                        Absolutely! We take security seriously:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 ml-4 space-y-1">
                        <li><strong>SSL Encryption:</strong> All data transmitted is encrypted</li>
                        <li><strong>Secure Hosting:</strong> Hosted on reliable cloud infrastructure</li>
                        <li><strong>Regular Backups:</strong> Daily automated backups</li>
                        <li><strong>Access Control:</strong> Only you can access your business data</li>
                        <li><strong>Payment Security:</strong> Paystack handles all payment processing securely</li>
                    </ul>
                </div>

                <!-- Question 20 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-pink-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Can I delete my account?</h3>
                    <p class="text-gray-700">
                        Yes, you can request account deletion at any time. Contact us at
                        <a href="mailto:info@khan.ng" class="text-purple-600 hover:underline">info@khan.ng</a>
                        and we'll permanently delete your account and all associated data within 30 days.
                    </p>
                </div>
            </div>
        </div>

        <!-- Support -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-8 h-8 text-teal-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                Support & Help
            </h2>

            <div class="space-y-6">
                <!-- Question 21 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-teal-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">How do I get help?</h3>
                    <p class="text-gray-700 mb-2">
                        We're here to help! Contact us through:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 ml-4 space-y-1">
                        <li><strong>Email:</strong> <a href="mailto:info@khan.ng" class="text-purple-600 hover:underline">info@khan.ng</a></li>
                        <li><strong>WhatsApp:</strong> <a href="https://wa.me/2348168166109" class="text-green-600 hover:underline">+234 816 816 6109</a></li>
                        <li><strong>Contact Form:</strong> <a href="/contact" class="text-purple-600 hover:underline">Send us a message</a></li>
                    </ul>
                    <p class="text-gray-700 mt-2">
                        We typically respond within 24 hours during business days (Monday - Friday, 9 AM - 5 PM WAT).
                    </p>
                </div>

                <!-- Question 22 -->
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-teal-600">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">I found a bug or have a feature request</h3>
                    <p class="text-gray-700">
                        We love hearing from our users! Please send bug reports or feature suggestions to
                        <a href="mailto:info@khan.ng" class="text-purple-600 hover:underline">info@khan.ng</a> or
                        use our <a href="/contact" class="text-purple-600 hover:underline">contact form</a>.
                        Your feedback helps us improve Khan Invoice for everyone.
                    </p>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl p-8 text-center">
            <h2 class="text-3xl font-bold mb-4">Still Have Questions?</h2>
            <p class="text-xl text-purple-100 mb-6">
                Can't find what you're looking for? We're here to help!
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/contact" class="inline-block bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    Contact Support
                </a>
                <a href="/app" class="inline-block bg-purple-800 text-white px-8 py-3 rounded-lg font-semibold hover:bg-purple-900 transition">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</x-layout>
