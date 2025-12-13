<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Quick Links --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Quick Links</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="#authentication" class="flex items-center gap-3 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition">
                    <x-filament::icon icon="heroicon-o-key" class="w-6 h-6 text-primary-600" />
                    <span class="font-medium">Authentication</span>
                </a>
                <a href="#endpoints" class="flex items-center gap-3 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition">
                    <x-filament::icon icon="heroicon-o-server" class="w-6 h-6 text-primary-600" />
                    <span class="font-medium">API Endpoints</span>
                </a>
                <a href="#examples" class="flex items-center gap-3 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition">
                    <x-filament::icon icon="heroicon-o-code-bracket" class="w-6 h-6 text-primary-600" />
                    <span class="font-medium">Code Examples</span>
                </a>
            </div>
        </div>

        {{-- API Base Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-start gap-3 mb-4">
                <x-filament::icon icon="heroicon-o-information-circle" class="w-6 h-6 text-primary-600 mt-1" />
                <div>
                    <h2 class="text-lg font-semibold mb-2">API Base URL</h2>
                    <code class="bg-gray-100 dark:bg-gray-900 px-4 py-2 rounded text-sm block">{{ url('/api/v1') }}</code>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">
                All API requests should be made to this base URL with the appropriate endpoint path.
            </p>
        </div>

        {{-- Full Documentation --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="prose prose-sm dark:prose-invert max-w-none">
                {!! $this->parseMarkdown($this->getApiDocumentation()) !!}
            </div>
        </div>

        {{-- Need Help? --}}
        <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-6">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-question-mark-circle" class="w-6 h-6 text-primary-600 mt-1" />
                <div>
                    <h3 class="font-semibold mb-2">Need Help?</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        If you need assistance with the API, please check:
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>Review the <a href="{{ route('filament.app.pages.api-settings') }}" class="text-primary-600 hover:underline">API Settings</a> page to manage your tokens</li>
                        <li>Ensure your API access is enabled</li>
                        <li>Check your rate limits</li>
                        <li>Verify your authentication token is valid</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Improve code block styling */
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        pre code {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        /* Smooth scroll for anchor links */
        html {
            scroll-behavior: smooth;
        }

        /* Style for tables if any in markdown */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }

        table th {
            background: rgb(243 244 246);
            font-weight: 600;
            padding: 0.75rem;
            text-align: left;
            border: 1px solid rgb(229 231 235);
        }

        .dark table th {
            background: rgb(31 41 55);
            border-color: rgb(55 65 81);
        }

        table td {
            padding: 0.75rem;
            border: 1px solid rgb(229 231 235);
        }

        .dark table td {
            border-color: rgb(55 65 81);
        }
    </style>
</x-filament-panels::page>
