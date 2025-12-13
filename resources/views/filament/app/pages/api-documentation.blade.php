<x-filament-panels::page>
    <div class="space-y-6">
        {{-- API Base Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-start gap-3 mb-4">
                <x-filament::icon icon="heroicon-o-information-circle" class="w-6 h-6 text-primary-600 mt-1" />
                <div class="flex-1">
                    <h2 class="text-lg font-semibold mb-2">API Base URL</h2>
                    <div class="flex items-center gap-2">
                        <code class="bg-gray-100 dark:bg-gray-900 px-4 py-2 rounded text-sm flex-1">{{ url('/api/v1') }}</code>
                        <button
                            onclick="navigator.clipboard.writeText('{{ url('/api/v1') }}')"
                            class="px-3 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 transition text-sm"
                        >
                            Copy
                        </button>
                    </div>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">
                All API requests should be made to this base URL with the appropriate endpoint path.
            </p>
        </div>

        {{-- Search Box --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="relative">
                <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input
                    type="text"
                    id="searchBox"
                    placeholder="Search documentation (e.g., authentication, invoices, customers)..."
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    x-data
                    @input="filterSections($event.target.value)"
                />
            </div>
        </div>

        {{-- Collapsible Sections --}}
        <div class="space-y-3" x-data="{ openSection: null }">
            @foreach($this->getApiDocumentation() as $index => $section)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden section-item" data-title="{{ strtolower($section['title']) }}">
                    {{-- Section Header (Clickable) --}}
                    <button
                        @click="openSection = openSection === {{ $index }} ? null : {{ $index }}"
                        class="w-full flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                    >
                        <div class="flex items-center gap-3">
                            <x-filament::icon :icon="$section['icon']" class="w-6 h-6 text-primary-600" />
                            <h3 class="text-lg font-semibold text-left">{{ $section['title'] }}</h3>
                        </div>
                        <x-filament::icon
                            icon="heroicon-o-chevron-down"
                            class="w-5 h-5 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openSection === {{ $index }} }"
                        />
                    </button>

                    {{-- Section Content (Collapsible) --}}
                    <div
                        x-show="openSection === {{ $index }}"
                        x-collapse
                        class="border-t border-gray-200 dark:border-gray-700"
                    >
                        <div class="p-6 prose prose-sm dark:prose-invert max-w-none">
                            {!! $this->parseMarkdown($section['content']) !!}
                        </div>
                    </div>
                </div>
            @endforeach
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
                        <li>Ensure your email is verified to create API tokens</li>
                        <li>Check your rate limits in API Settings</li>
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

        /* Hide sections that don't match search */
        .section-item.hidden {
            display: none;
        }
    </style>

    <script>
        function filterSections(query) {
            const sections = document.querySelectorAll('.section-item');
            const searchQuery = query.toLowerCase().trim();

            sections.forEach(section => {
                const title = section.getAttribute('data-title');
                const content = section.textContent.toLowerCase();

                if (searchQuery === '' || title.includes(searchQuery) || content.includes(searchQuery)) {
                    section.classList.remove('hidden');
                } else {
                    section.classList.add('hidden');
                }
            });
        }

        // Copy button feedback
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('button[onclick*="clipboard"]').forEach(button => {
                button.addEventListener('click', function() {
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    this.classList.add('bg-green-600');
                    this.classList.remove('bg-primary-600');

                    setTimeout(() => {
                        this.textContent = originalText;
                        this.classList.remove('bg-green-600');
                        this.classList.add('bg-primary-600');
                    }, 2000);
                });
            });
        });
    </script>
</x-filament-panels::page>
