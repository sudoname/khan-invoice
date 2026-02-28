<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header with search and filters --}}
        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
            <div class="flex-1">
                <h2 class="text-2xl font-bold tracking-tight">Choose a Template</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Select from our collection of professionally designed templates
                </p>
            </div>

            {{-- Search box --}}
            <div class="w-full md:w-96">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="search"
                        wire:model.live="searchQuery"
                        placeholder="Search templates..."
                        prefix-icon="heroicon-m-magnifying-glass"
                    />
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- Category filters --}}
        <div class="flex flex-wrap gap-2">
            <button
                wire:click="filterByCategory('all')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $selectedCategory === 'all' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
            >
                All Templates
            </button>

            @foreach ($this->getCategories() as $category)
                <button
                    wire:click="filterByCategory('{{ $category['value'] }}')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition inline-flex items-center gap-2 {{ $selectedCategory === $category['value'] ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                >
                    <x-filament::icon
                        :icon="$category['icon']"
                        class="w-4 h-4"
                    />
                    {{ $category['label'] }}
                </button>
            @endforeach
        </div>

        {{-- Templates grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse ($this->getTemplates() as $template)
                <div class="group relative bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all duration-200">
                    {{-- Template preview --}}
                    <div class="aspect-[{{ $template['aspect_ratio'] === '9:16' ? '9/16' : ($template['aspect_ratio'] === '16:9' ? '16/9' : '1/1') }}] bg-gray-100 dark:bg-gray-900 relative overflow-hidden">
                        @if ($template['preview_url'])
                            <img
                                src="{{ $template['preview_url'] }}"
                                alt="{{ $template['name'] }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                            />
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <x-filament::icon
                                    icon="heroicon-o-photo"
                                    class="w-16 h-16 text-gray-400"
                                />
                            </div>
                        @endif

                        {{-- Premium badge --}}
                        @if ($template['is_premium'])
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-amber-500 text-white">
                                    <x-filament::icon
                                        icon="heroicon-m-star"
                                        class="w-3 h-3"
                                    />
                                    Premium
                                </span>
                            </div>
                        @endif

                        {{-- Hover overlay --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/0 to-black/0 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-end justify-center p-4">
                            <button
                                wire:click="createFromTemplate({{ $template['id'] }})"
                                class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition transform translate-y-2 group-hover:translate-y-0"
                            >
                                <span class="flex items-center justify-center gap-2">
                                    <x-filament::icon
                                        icon="heroicon-m-sparkles"
                                        class="w-4 h-4"
                                    />
                                    Use Template
                                </span>
                            </button>
                        </div>
                    </div>

                    {{-- Template details --}}
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                            {{ $template['name'] }}
                        </h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                            {{ $template['description'] }}
                        </p>

                        <div class="flex items-center justify-between text-xs">
                            <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                <x-filament::icon
                                    icon="heroicon-m-rectangle-group"
                                    class="w-3 h-3"
                                />
                                {{ $template['width'] }} × {{ $template['height'] }}
                            </span>

                            <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                <x-filament::icon
                                    icon="heroicon-m-users"
                                    class="w-3 h-3"
                                />
                                {{ number_format($template['usage_count']) }} uses
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <x-filament::icon
                        icon="heroicon-o-magnifying-glass"
                        class="w-16 h-16 text-gray-400 mx-auto mb-4"
                    />
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        No templates found
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Try adjusting your search or filter criteria
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Template count --}}
        @if ($this->getTemplates()->isNotEmpty())
            <div class="text-center text-sm text-gray-600 dark:text-gray-400">
                Showing {{ $this->getTemplates()->count() }} template(s)
            </div>
        @endif
    </div>
</x-filament-panels::page>
