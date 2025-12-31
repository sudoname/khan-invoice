<div class="bg-blue-50 border-l-4 border-blue-500 rounded-r-lg p-5 flex items-start justify-between gap-4">
    <div class="flex items-start flex-1">
        <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            <h4 class="text-sm font-bold text-blue-900 mb-1">💡 Quick Tip</h4>
            <p class="text-sm text-blue-800">
                Record payments to know who still owes you. This helps track outstanding balances and keeps your books organized.
            </p>
        </div>
    </div>
    <a href="{{ route('filament.app.resources.payments.create') }}"
       class="inline-flex items-center justify-center bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition text-sm whitespace-nowrap shadow-sm">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Record Payment
    </a>
</div>
