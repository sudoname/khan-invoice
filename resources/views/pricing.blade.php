<x-layout>
<section class="gradient-bg text-white py-16">
<div class="max-w-7xl mx-auto px-4 text-center">
<h1 class="text-4xl font-bold mb-4">Choose Your Plan</h1>
<p class="text-xl text-purple-100">Flexible pricing for all business sizes</p>
</div>
</section>
<section class="py-16 bg-gray-50" x-data="{ cycle: 'monthly' }">
<div class="max-w-7xl mx-auto px-4">
<div class="flex justify-center mb-12">
<div class="inline-flex bg-white rounded-lg p-1 shadow">
<button @click="cycle='monthly'" :class="cycle==='monthly'?'bg-purple-600 text-white':'text-gray-700'" class="px-6 py-2 rounded-md font-semibold">Monthly</button>
<button @click="cycle='yearly'" :class="cycle==='yearly'?'bg-purple-600 text-white':'text-gray-700'" class="px-6 py-2 rounded-md font-semibold">Yearly <span class="ml-2 text-xs bg-green-500 text-white px-2 py-1 rounded-full">Save 17%</span></button>
</div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
@foreach($plans as $plan)
<div class="bg-white rounded-xl shadow-lg p-8 {{ $plan->is_popular ? 'ring-4 ring-purple-600' : '' }}">
@if($plan->is_popular)<div class="bg-purple-600 text-white text-center py-2 -mx-8 -mt-8 mb-4 font-semibold text-sm">MOST POPULAR</div>@endif
<h3 class="text-2xl font-bold mb-2">{{ $plan->name }}</h3>
<p class="text-gray-600 mb-6">{{ $plan->description }}</p>
<div class="mb-6">
<div x-show="cycle==='monthly'"><span class="text-4xl font-bold">₦{{ number_format($plan->price_monthly) }}</span><span class="text-gray-600">/month</span></div>
<div x-show="cycle==='yearly'" x-cloak><span class="text-4xl font-bold">₦{{ number_format($plan->price_yearly) }}</span><span class="text-gray-600">/year</span></div>
</div>
<form action="{{ route('pricing.select', $plan->slug) }}" method="POST">
@csrf
<input type="hidden" name="cycle" x-bind:value="cycle">
<button type="submit" class="{{ $plan->is_popular ? 'bg-purple-600 hover:bg-purple-700' : 'bg-gray-800 hover:bg-gray-900' }} text-white w-full py-3 rounded-lg font-semibold">{{ $plan->isFree() ? 'Get Started Free' : 'Choose Plan' }}</button>
</form>
<div class="mt-8 space-y-3">
<p class="text-sm font-semibold uppercase">Features:</p>
<div class="flex"><svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span class="text-sm">@if($plan->max_invoices==-1)Unlimited@else{{ $plan->max_invoices }}@endif invoices</span></div>
<div class="flex"><svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span class="text-sm">@if($plan->max_customers==-1)Unlimited@else{{ $plan->max_customers }}@endif customers</span></div>
@if($plan->sms_credits_monthly>0)<div class="flex"><svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span class="text-sm">{{ number_format($plan->sms_credits_monthly) }} SMS/month</span></div>@endif
@if($plan->multi_currency)<div class="flex"><svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span class="text-sm">Multi-currency</span></div>@endif
@if($plan->api_access)<div class="flex"><svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span class="text-sm">API access</span></div>@endif
</div>
</div>
@endforeach
</div>
</div>
</section>
</x-layout>