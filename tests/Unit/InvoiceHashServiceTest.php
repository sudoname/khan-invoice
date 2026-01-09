<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PublicInvoice;
use App\Models\Customer;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Services\Invoice\InvoiceHashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceHashServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceHashService $hashService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hashService = new InvoiceHashService();
    }

    /** @test */
    public function it_generates_deterministic_hash_for_same_invoice()
    {
        $invoice = $this->createTestInvoice();

        $hash1 = $this->hashService->computeHash($invoice);
        $hash2 = $this->hashService->computeHash($invoice);

        $this->assertEquals($hash1, $hash2, 'Hash should be deterministic for same invoice');
        $this->assertEquals(64, strlen($hash1), 'Hash should be 64 characters (SHA-256 hex)');
    }

    /** @test */
    public function it_changes_hash_when_line_item_changes()
    {
        $invoice = $this->createTestInvoice();
        $hash1 = $this->hashService->computeHash($invoice);

        // Change line item quantity
        $invoice->items->first()->update(['quantity' => 5]);
        $invoice->refresh();
        $invoice->calculateTotals();

        $hash2 = $this->hashService->computeHash($invoice);

        $this->assertNotEquals($hash1, $hash2, 'Hash should change when line item quantity changes');
    }

    /** @test */
    public function it_changes_hash_when_line_item_price_changes()
    {
        $invoice = $this->createTestInvoice();
        $hash1 = $this->hashService->computeHash($invoice);

        // Change line item price
        $invoice->items->first()->update(['unit_price' => 2000]);
        $invoice->refresh();
        $invoice->calculateTotals();

        $hash2 = $this->hashService->computeHash($invoice);

        $this->assertNotEquals($hash1, $hash2, 'Hash should change when line item price changes');
    }

    /** @test */
    public function it_changes_hash_when_line_item_description_changes()
    {
        $invoice = $this->createTestInvoice();
        $hash1 = $this->hashService->computeHash($invoice);

        // Change line item description
        $invoice->items->first()->update(['description' => 'Modified Service Description']);
        $invoice->refresh();

        $hash2 = $this->hashService->computeHash($invoice);

        $this->assertNotEquals($hash1, $hash2, 'Hash should change when line item description changes');
    }

    /** @test */
    public function it_changes_hash_when_vat_rate_changes()
    {
        $invoice = $this->createTestInvoice();
        $hash1 = $this->hashService->computeHash($invoice);

        // Change VAT rate
        $invoice->update(['vat_rate' => 10]);
        $invoice->refresh();

        $hash2 = $this->hashService->computeHash($invoice);

        $this->assertNotEquals($hash1, $hash2, 'Hash should change when VAT rate changes');
    }

    /** @test */
    public function it_changes_hash_when_customer_info_changes()
    {
        $invoice = $this->createTestInvoice();
        $hash1 = $this->hashService->computeHash($invoice);

        // Change customer name
        $invoice->customer->update(['name' => 'Modified Customer Name']);
        $invoice->refresh();

        $hash2 = $this->hashService->computeHash($invoice);

        $this->assertNotEquals($hash1, $hash2, 'Hash should change when customer info changes');
    }

    /** @test */
    public function it_changes_hash_when_business_profile_changes()
    {
        $invoice = $this->createTestInvoice();
        $hash1 = $this->hashService->computeHash($invoice);

        // Change business name
        $invoice->businessProfile->update(['business_name' => 'Modified Business Name']);
        $invoice->refresh();

        $hash2 = $this->hashService->computeHash($invoice);

        $this->assertNotEquals($hash1, $hash2, 'Hash should change when business profile changes');
    }

    /** @test */
    public function it_changes_hash_when_notes_change()
    {
        $invoice = $this->createTestInvoice();
        $hash1 = $this->hashService->computeHash($invoice);

        // Change notes
        $invoice->update(['notes' => 'Modified payment terms and conditions']);
        $invoice->refresh();

        $hash2 = $this->hashService->computeHash($invoice);

        $this->assertNotEquals($hash1, $hash2, 'Hash should change when notes change');
    }

    /** @test */
    public function it_generates_deterministic_hash_for_public_invoice()
    {
        $publicInvoice = $this->createTestPublicInvoice();

        $hash1 = $this->hashService->computeHash($publicInvoice);
        $hash2 = $this->hashService->computeHash($publicInvoice);

        $this->assertEquals($hash1, $hash2, 'Hash should be deterministic for same public invoice');
        $this->assertEquals(64, strlen($hash1), 'Hash should be 64 characters (SHA-256 hex)');
    }

    /** @test */
    public function it_changes_public_invoice_hash_when_from_name_changes()
    {
        $publicInvoice = $this->createTestPublicInvoice();
        $hash1 = $this->hashService->computeHash($publicInvoice);

        // Change from name
        $publicInvoice->update(['from_name' => 'Modified Business Name']);
        $publicInvoice->refresh();

        $hash2 = $this->hashService->computeHash($publicInvoice);

        $this->assertNotEquals($hash1, $hash2, 'Hash should change when from_name changes');
    }

    /** @test */
    public function it_changes_public_invoice_hash_when_items_change()
    {
        $publicInvoice = $this->createTestPublicInvoice();
        $hash1 = $this->hashService->computeHash($publicInvoice);

        // Modify items
        $items = $publicInvoice->items;
        $items[0]['quantity'] = 5;
        $publicInvoice->update(['items' => $items]);
        $publicInvoice->refresh();

        $hash2 = $this->hashService->computeHash($publicInvoice);

        $this->assertNotEquals($hash1, $hash2, 'Hash should change when public invoice items change');
    }

    /** @test */
    public function it_normalizes_whitespace_in_strings()
    {
        $invoice1 = $this->createTestInvoice();
        $invoice1->update(['notes' => 'Payment  terms   with   extra  spaces']);
        $invoice1->refresh();

        $invoice2 = $this->createTestInvoice();
        $invoice2->update(['notes' => 'Payment terms with extra spaces']);
        $invoice2->refresh();

        $hash1 = $this->hashService->computeHash($invoice1);
        $hash2 = $this->hashService->computeHash($invoice2);

        $this->assertEquals($hash1, $hash2, 'Hash should be same after whitespace normalization');
    }

    /**
     * Create a test invoice with all relationships
     */
    protected function createTestInvoice(): Invoice
    {
        $user = User::factory()->create();

        $customer = Customer::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
        ]);

        $businessProfile = BusinessProfile::factory()->create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'business_profile_id' => $businessProfile->id,
            'invoice_number' => 'INV-2026-00000001',
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'draft',
            'currency' => 'NGN',
            'sub_total' => 0,
            'vat_rate' => 7.5,
            'vat_amount' => 0,
            'wht_rate' => 0,
            'wht_amount' => 0,
            'discount_total' => 0,
            'total_amount' => 0,
            'amount_due' => 0,
            'notes' => 'Payment due within 30 days',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Service',
            'quantity' => 2,
            'unit_price' => 1000,
            'discount' => 0,
            'tax_rate' => 0,
            'line_total' => 2000,
        ]);

        $invoice->refresh();
        $invoice->calculateTotals();

        return $invoice->fresh();
    }

    /**
     * Create a test public invoice
     */
    protected function createTestPublicInvoice(): PublicInvoice
    {
        return PublicInvoice::create([
            'public_id' => \Str::random(12),
            'invoice_number' => 'INV-2026-00000001',
            'from_name' => 'Test Business',
            'from_email' => 'business@test.com',
            'to_name' => 'Test Customer',
            'to_email' => 'customer@test.com',
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'items' => [
                [
                    'description' => 'Test Service',
                    'quantity' => 2,
                    'unit_price' => 1000,
                    'total' => 2000,
                ],
            ],
            'subtotal' => 2000,
            'vat_percentage' => 7.5,
            'vat_amount' => 150,
            'total_amount' => 2150,
            'notes' => 'Payment due within 30 days',
        ]);
    }
}
