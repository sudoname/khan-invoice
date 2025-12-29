<x-filament-panels::page>
    <!-- Filters -->
    <div class="mb-6 flex flex-wrap gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
            <select wire:model.live="dateRange" class="rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="1">Last 24 hours</option>
                <option value="7">Last 7 days</option>
                <option value="14">Last 14 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Event Filter</label>
            <select wire:model.live="eventFilter" class="rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">All Events</option>
                @foreach($this->getAvailableEvents() as $event)
                    <option value="{{ $event }}">{{ $event }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @php $stats = $this->getStats(); @endphp

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">Total Events</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_events']) }}</p>
                    @if($stats['total_events_change'] != 0)
                        <div class="flex items-center mt-2">
                            @if($stats['total_events_change'] > 0)
                                <svg class="w-4 h-4 text-green-600 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                                <span class="text-sm font-semibold text-green-600">{{ abs($stats['total_events_change']) }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-600 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                                <span class="text-sm font-semibold text-red-600">{{ abs($stats['total_events_change']) }}%</span>
                            @endif
                            <span class="text-xs text-gray-500 ml-1">vs previous period</span>
                        </div>
                    @endif
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">Unique Sessions</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['unique_sessions']) }}</p>
                    @if($stats['unique_sessions_change'] != 0)
                        <div class="flex items-center mt-2">
                            @if($stats['unique_sessions_change'] > 0)
                                <svg class="w-4 h-4 text-green-600 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                                <span class="text-sm font-semibold text-green-600">{{ abs($stats['unique_sessions_change']) }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-600 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                                <span class="text-sm font-semibold text-red-600">{{ abs($stats['unique_sessions_change']) }}%</span>
                            @endif
                            <span class="text-xs text-gray-500 ml-1">vs previous period</span>
                        </div>
                    @endif
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">Registered Users</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['unique_users']) }}</p>
                    @if($stats['unique_users_change'] != 0)
                        <div class="flex items-center mt-2">
                            @if($stats['unique_users_change'] > 0)
                                <svg class="w-4 h-4 text-green-600 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                                <span class="text-sm font-semibold text-green-600">{{ abs($stats['unique_users_change']) }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-600 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                                <span class="text-sm font-semibold text-red-600">{{ abs($stats['unique_users_change']) }}%</span>
                            @endif
                            <span class="text-xs text-gray-500 ml-1">vs previous period</span>
                        </div>
                    @endif
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversion Funnel -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">FREE User Funnel</h3>
        @php $funnel = $this->getConversionFunnel(); @endphp

        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Landing Page Views</span>
                    <span class="text-sm font-bold text-gray-900">{{ number_format($funnel['landing_views']) }}</span>
                </div>
                <div class="w-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg h-12 flex items-center justify-center shadow-sm">
                    <span class="text-white font-semibold text-sm">100% · {{ number_format($funnel['landing_views']) }} visitors</span>
                </div>
            </div>

            <div>
                @php $pct = $funnel['landing_views'] > 0 ? ($funnel['generator_views'] / $funnel['landing_views']) * 100 : 0; @endphp
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Generator Views</span>
                    <span class="text-sm font-bold text-gray-900">{{ number_format($funnel['generator_views']) }} <span class="text-xs text-gray-500">({{ number_format($pct, 1) }}%)</span></span>
                </div>
                <div class="w-full bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-lg h-12 flex items-center justify-center shadow-sm">
                    <span class="text-white font-semibold text-sm">{{ number_format($pct, 1) }}% · {{ number_format($funnel['generator_views']) }} started</span>
                </div>
            </div>

            <div>
                @php $pct = $funnel['generator_views'] > 0 ? ($funnel['invoices_generated'] / $funnel['generator_views']) * 100 : 0; @endphp
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Invoices Generated</span>
                    <span class="text-sm font-bold text-gray-900">{{ number_format($funnel['invoices_generated']) }} <span class="text-xs text-gray-500">({{ number_format($pct, 1) }}%)</span></span>
                </div>
                <div class="w-full bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg h-12 flex items-center justify-center shadow-sm">
                    <span class="text-white font-semibold text-sm">{{ number_format($pct, 1) }}% · {{ number_format($funnel['invoices_generated']) }} invoices</span>
                </div>
            </div>

            <div>
                @php $pct = $funnel['invoices_generated'] > 0 ? ($funnel['pdf_downloads'] / $funnel['invoices_generated']) * 100 : 0; @endphp
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">PDF Downloads</span>
                    <span class="text-sm font-bold text-gray-900">{{ number_format($funnel['pdf_downloads']) }} <span class="text-xs text-gray-500">({{ number_format($pct, 1) }}%)</span></span>
                </div>
                <div class="w-full bg-gradient-to-r from-amber-500 to-amber-600 rounded-lg h-12 flex items-center justify-center shadow-sm">
                    <span class="text-white font-semibold text-sm">{{ number_format($pct, 1) }}% · {{ number_format($funnel['pdf_downloads']) }} downloads</span>
                </div>
            </div>

            <div>
                @php $pct = $funnel['invoices_generated'] > 0 ? ($funnel['signup_prompt_shown'] / $funnel['invoices_generated']) * 100 : 0; @endphp
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Signup Prompts Shown</span>
                    <span class="text-sm font-bold text-gray-900">{{ number_format($funnel['signup_prompt_shown']) }} <span class="text-xs text-gray-500">({{ number_format($pct, 1) }}%)</span></span>
                </div>
                <div class="w-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-lg h-12 flex items-center justify-center shadow-sm">
                    <span class="text-white font-semibold text-sm">{{ number_format($pct, 1) }}% · {{ number_format($funnel['signup_prompt_shown']) }} prompts</span>
                </div>
            </div>

            <div>
                @php $pct = $funnel['signup_prompt_shown'] > 0 ? ($funnel['signup_prompt_clicked'] / $funnel['signup_prompt_shown']) * 100 : 0; @endphp
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Signup Clicks</span>
                    <span class="text-sm font-bold text-gray-900">{{ number_format($funnel['signup_prompt_clicked']) }} <span class="text-xs text-gray-500">({{ number_format($pct, 1) }}%)</span></span>
                </div>
                <div class="w-full bg-gradient-to-r from-pink-500 to-pink-600 rounded-lg h-12 flex items-center justify-center shadow-sm">
                    <span class="text-white font-semibold text-sm">{{ number_format($pct, 1) }}% · {{ number_format($funnel['signup_prompt_clicked']) }} clicked</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Breakdown and Top Paths -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Event Breakdown -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Event Breakdown (Top 20)</h3>
            @php $breakdown = $this->getEventBreakdown(); @endphp

            @if(count($breakdown) > 0)
                <div class="space-y-2">
                    @foreach($breakdown as $event => $count)
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700 truncate flex-1 mr-4">{{ $event }}</span>
                            <span class="text-gray-900 font-semibold">{{ number_format($count) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">No events found for this period.</p>
            @endif
        </div>

        <!-- Top Paths -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Pages (Top 10)</h3>
            @php $paths = $this->getTopPaths(); @endphp

            @if(count($paths) > 0)
                <div class="space-y-2">
                    @foreach($paths as $path => $count)
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700 truncate flex-1 mr-4">{{ $path }}</span>
                            <span class="text-gray-900 font-semibold">{{ number_format($count) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">No page data found for this period.</p>
            @endif
        </div>
    </div>

    <!-- Recent Events Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Events (Last 100)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Path</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Properties</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($this->getRecentEvents() as $event)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $event['name'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $event['occurred_at'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $event['path'] ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $event['user_id'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ $event['session_id'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ $event['properties'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
