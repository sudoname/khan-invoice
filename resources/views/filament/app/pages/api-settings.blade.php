<x-filament-panels::page>
    <div class="space-y-6">
        <!-- API Settings Form -->
        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button type="submit">
                    Save Settings
                </x-filament::button>
            </div>
        </form>

        <!-- Create New Token Section -->
        @if(auth()->user()->api_enabled)
            <x-filament::section>
                <x-slot name="heading">
                    Create New API Token
                </x-slot>

                <x-slot name="description">
                    Generate a new API token for external applications
                </x-slot>

                <div class="space-y-4">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model="newTokenName"
                            placeholder="My Application Token"
                        />
                    </x-filament::input.wrapper>

                    <x-filament::button wire:click="createToken">
                        Generate Token
                    </x-filament::button>

                    @if($generatedToken)
                        <div class="p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                            <p class="text-sm font-semibold text-warning-900 dark:text-warning-100 mb-2">
                                Your new API token (copy it now, you won't see it again):
                            </p>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 p-2 bg-gray-50 dark:bg-gray-800 rounded text-sm font-mono break-all">
                                    {{ $generatedToken }}
                                </code>
                                <x-filament::button
                                    size="sm"
                                    onclick="navigator.clipboard.writeText('{{ $generatedToken }}'); alert('Token copied to clipboard!');"
                                >
                                    Copy
                                </x-filament::button>
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

        <!-- Active Tokens Table -->
        <x-filament::section>
            <x-slot name="heading">
                Active API Tokens
            </x-slot>

            <x-slot name="description">
                Manage your existing API tokens
            </x-slot>

            {{ $this->table }}
        </x-filament::section>

        <!-- API Usage Example -->
        @if(auth()->user()->api_enabled)
            <x-filament::section
                collapsible
                collapsed
            >
                <x-slot name="heading">
                    API Usage Example
                </x-slot>

                <x-slot name="description">
                    Quick start guide for using the API
                </x-slot>

                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-semibold mb-2">1. Create a Token</h4>
                        <pre class="p-4 bg-gray-50 dark:bg-gray-800 rounded text-xs overflow-x-auto"><code>curl -X POST {{ url('/api/v1/auth/token') }} \
  -H "Content-Type: application/json" \
  -d '{
    "email": "{{ auth()->user()->email }}",
    "password": "your_password",
    "token_name": "My Application"
  }'</code></pre>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold mb-2">2. List Invoices</h4>
                        <pre class="p-4 bg-gray-50 dark:bg-gray-800 rounded text-xs overflow-x-auto"><code>curl {{ url('/api/v1/invoices') }} \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"</code></pre>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold mb-2">3. Create Invoice</h4>
                        <pre class="p-4 bg-gray-50 dark:bg-gray-800 rounded text-xs overflow-x-auto"><code>curl -X POST {{ url('/api/v1/invoices') }} \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 1,
    "business_profile_id": 1,
    "issue_date": "2025-12-12",
    "due_date": "2026-01-12",
    "currency": "USD",
    "items": [
      {
        "description": "Web Development",
        "quantity": 1,
        "unit_price": 1000
      }
    ]
  }'</code></pre>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold mb-2">4. Get Reports</h4>
                        <pre class="p-4 bg-gray-50 dark:bg-gray-800 rounded text-xs overflow-x-auto"><code>curl "{{ url('/api/v1/reports/sales?start_date=2025-01-01&end_date=2025-12-31') }}" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"</code></pre>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
