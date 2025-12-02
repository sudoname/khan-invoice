<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            {{ $this->getFormActions()[0] }}
        </div>
    </form>

    <!-- Preview Section -->
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Fee Calculation Preview
        </x-slot>

        <x-slot name="description">
            See how fees are calculated based on current settings
        </x-slot>

        <div class="space-y-4">
            @php
                $testAmounts = [1000, 5000, 10000, 50000, 100000];
                $paystackFeePercentage = (float) ($data['paystack_fee_percentage'] ?? 1.5);
                $paystackFeeMinimum = (float) ($data['paystack_fee_minimum'] ?? 100);
                $paystackFeeCap = (float) ($data['paystack_fee_cap'] ?? 3000);
                $serviceChargePercentage = (float) ($data['service_charge_percentage'] ?? 2);
                $serviceChargeMinimum = (float) ($data['service_charge_minimum'] ?? 150);
                $serviceChargeCap = (float) ($data['service_charge_cap'] ?? 3000);
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Invoice Amount</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Paystack Fee</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Service Charge</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Total Fees</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Customer Pays</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($testAmounts as $amount)
                            @php
                                // Paystack fee: percentage + fixed amount, capped at maximum
                                $paystackPercentageFee = $amount * ($paystackFeePercentage / 100);
                                $paystackFee = min($paystackPercentageFee + $paystackFeeMinimum, $paystackFeeCap);

                                // Service charge: max(percentage, minimum), capped at maximum
                                $serviceCharge = min(max($amount * ($serviceChargePercentage / 100), $serviceChargeMinimum), $serviceChargeCap);

                                $totalFees = $paystackFee + $serviceCharge;
                                $customerPays = $amount + $totalFees;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-900">₦{{ number_format($amount, 2) }}</td>
                                <td class="px-4 py-3 text-blue-600">₦{{ number_format($paystackFee, 2) }}</td>
                                <td class="px-4 py-3 text-purple-600">₦{{ number_format($serviceCharge, 2) }}</td>
                                <td class="px-4 py-3 text-orange-600 font-semibold">₦{{ number_format($totalFees, 2) }}</td>
                                <td class="px-4 py-3 text-green-600 font-bold">₦{{ number_format($customerPays, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                <p class="text-sm text-blue-800">
                    <strong>Fee Calculation Formula:</strong><br>
                    • <strong>Paystack Fee:</strong> (Percentage + Fixed Amount), capped at maximum<br>
                    • <strong>Service Charge:</strong> max(Percentage, Minimum), capped at maximum<br>
                    • Both fees apply to all Paystack payments and are charged to customers.
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
