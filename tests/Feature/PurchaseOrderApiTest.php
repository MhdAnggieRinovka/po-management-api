<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_list_purchase_orders(): void
    {
        $this->getJson('/api/purchase-orders')->assertUnauthorized();
    }

    public function test_sales_user_can_create_a_purchase_order_with_items(): void
    {
        $sales = User::factory()->create(['role' => 'sales', 'is_active' => true]);
        $supplier = Supplier::create([
            'supplier_code' => 'SUP-001',
            'name' => 'Supplier Test',
            'is_active' => true,
        ]);
        $product = Product::create([
            'product_code' => 'PRD-001',
            'name' => 'Product Test',
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $response = $this->actingAs($sales)->postJson('/api/purchase-orders', [
            'po_number' => 'PO-TEST-001',
            'supplier_id' => $supplier->id,
            'order_date' => '2026-10-25',
            'expected_delivery_date' => '2026-11-01',
            'items' => [[
                'product_id' => $product->id,
                'description' => 'Product Test',
                'quantity' => 5,
                'unit_price' => 12000,
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.po_number', 'PO-TEST-001')
            ->assertJsonPath('data.status', PurchaseOrder::STATUS_OPEN);

        $this->assertDatabaseHas('purchase_orders', [
            'po_number' => 'PO-TEST-001',
            'created_by' => $sales->id,
        ]);
        $this->assertDatabaseHas('purchase_order_items', [
            'description' => 'Product Test',
            'quantity' => 5,
            'received_quantity' => 0,
        ]);
    }

    public function test_sales_user_cannot_receive_goods(): void
    {
        $sales = User::factory()->create(['role' => 'sales', 'is_active' => true]);
        $supplier = Supplier::create([
            'supplier_code' => 'SUP-002',
            'name' => 'Supplier Test 2',
            'is_active' => true,
        ]);
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-002',
            'supplier_id' => $supplier->id,
            'created_by' => $sales->id,
            'order_date' => '2026-10-25',
            'status' => PurchaseOrder::STATUS_OPEN,
        ]);

        $this->actingAs($sales)
            ->postJson('/api/purchase-orders/'.$po->id.'/receive', [
                'items' => [],
            ])
            ->assertForbidden();
    }
}
