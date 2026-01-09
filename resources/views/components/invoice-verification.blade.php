@props(['hash', 'updatedAt'])

<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mt-6">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                Document Verification
            </h3>
            <p class="text-xs text-gray-500 mb-3">
                This hash uniquely identifies the invoice content. It changes if any invoice details are modified.
            </p>

            @if($hash)
                <div class="space-y-2">
                    <div>
                        <label class="text-xs font-medium text-gray-600 block mb-1">Document Hash</label>
                        <div class="flex items-center gap-2">
                            <code id="document-hash" class="flex-1 text-xs bg-white px-3 py-2 rounded border border-gray-300 font-mono overflow-x-auto">{{ $hash }}</code>
                            <button
                                type="button"
                                onclick="copyHashToClipboard()"
                                class="px-3 py-2 bg-purple-600 text-white text-xs font-medium rounded hover:bg-purple-700 transition flex items-center gap-1"
                                title="Copy to clipboard"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                Copy
                            </button>
                        </div>
                    </div>

                    @if($updatedAt)
                        <div>
                            <label class="text-xs font-medium text-gray-600 block mb-1">Hash Last Updated</label>
                            <p class="text-xs text-gray-700">{{ is_string($updatedAt) ? \Carbon\Carbon::parse($updatedAt)->format('F j, Y \a\t g:i A') : $updatedAt->format('F j, Y \a\t g:i A') }}</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-xs text-gray-500 italic">
                    Hash is being generated... Please refresh the page in a moment.
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function copyHashToClipboard() {
    const hashElement = document.getElementById('document-hash');
    const hash = hashElement.textContent;

    navigator.clipboard.writeText(hash).then(() => {
        // Show success feedback
        const button = event.target.closest('button');
        const originalHTML = button.innerHTML;

        button.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Copied!
        `;
        button.classList.add('bg-green-600');
        button.classList.remove('bg-purple-600', 'hover:bg-purple-700');

        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('bg-green-600');
            button.classList.add('bg-purple-600', 'hover:bg-purple-700');
        }, 2000);
    }).catch(err => {
        alert('Failed to copy hash to clipboard');
        console.error('Copy failed:', err);
    });
}
</script>
