<div class="p-6">
    @if($design->isCompleted())
        <div class="mb-6 text-center">
            <img
                src="{{ $design->rendered_url }}"
                alt="{{ $design->title }}"
                class="mx-auto max-w-full rounded-lg shadow-lg"
                style="max-height: 70vh;"
            />
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-semibold">Dimensions:</span>
                {{ $design->width }}x{{ $design->height }}px
            </div>
            <div>
                <span class="font-semibold">File Size:</span>
                {{ round($design->file_size / 1024, 2) }} KB
            </div>
            <div>
                <span class="font-semibold">Template:</span>
                {{ $design->template->name }}
            </div>
            <div>
                <span class="font-semibold">Downloads:</span>
                {{ $design->download_count }}
            </div>
        </div>

        @if($design->prompt)
            <div class="mt-4">
                <span class="font-semibold">Original Prompt:</span>
                <p class="mt-2 text-gray-600 dark:text-gray-400">{{ $design->prompt }}</p>
            </div>
        @endif
    @elseif($design->isRendering())
        <div class="py-12 text-center">
            <div class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-t-2 border-primary-600"></div>
            <p class="text-lg font-semibold">Generating your design...</p>
            <p class="mt-2 text-sm text-gray-600">This usually takes less than 10 seconds</p>
        </div>
    @elseif($design->hasFailed())
        <div class="py-12 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-900">
                <svg class="h-6 w-6 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <p class="text-lg font-semibold">Design generation failed</p>
            @if($design->render_error)
                <p class="mt-2 text-sm text-gray-600">{{ $design->render_error }}</p>
            @endif
        </div>
    @else
        <div class="py-12 text-center">
            <p class="text-lg font-semibold">Design is in draft state</p>
            <p class="mt-2 text-sm text-gray-600">The design hasn't been rendered yet</p>
        </div>
    @endif
</div>
