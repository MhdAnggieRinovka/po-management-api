<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::query()->with(['supplier', 'creator', 'items.product'])->latest();

        if ($request->user()->role === 'sales') {
            $query->where('created_by', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json(['data' => $query->paginate(20)]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->role, ['admin', 'sales', 'warehouse'], true), 403);

        $data = $request->validate([
            'po_number' => ['required', 'string', 'max:60', 'unique:purchase_orders,po_number'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $po = DB::transaction(function () use ($data, $request) {
            $po = PurchaseOrder::create([
                'po_number' => $data['po_number'],
                'supplier_id' => $data['supplier_id'],
                'created_by' => $request->user()->id,
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => PurchaseOrder::STATUS_OPEN,
            ]);

            foreach ($data['items'] as $item) {
                $po->items()->create([
                    ...$item,
                    'received_quantity' => 0,
                ]);
            }

            return $po->load(['supplier', 'creator', 'items.product']);
        });

        return response()->json(['data' => $po], 201);
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($request->user()->role === 'sales' && $purchaseOrder->created_by !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['data' => $purchaseOrder->load(['supplier', 'creator', 'items.product', 'calendarEvents'])]);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        abort_unless(in_array($request->user()->role, ['admin', 'warehouse'], true), 403);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', Rule::exists('purchase_order_items', 'id')->where('purchase_order_id', $purchaseOrder->id)],
            'items.*.received_quantity' => ['required', 'numeric', 'gte:0'],
        ]);

        DB::transaction(function () use ($data, $purchaseOrder) {
            foreach ($data['items'] as $received) {
                $item = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                    ->lockForUpdate()->findOrFail($received['id']);

                $newTotal = (float) $item->received_quantity + (float) $received['received_quantity'];
                if ($newTotal > (float) $item->quantity) {
                    abort(422, 'Jumlah diterima tidak boleh melebihi jumlah pesanan.');
                }

                $item->update(['received_quantity' => $newTotal]);
            }

            $purchaseOrder->load('items');
            $allReceived = $purchaseOrder->items->every(fn ($item) => (float) $item->received_quantity >= (float) $item->quantity);
            $anyReceived = $purchaseOrder->items->contains(fn ($item) => (float) $item->received_quantity > 0);

            $purchaseOrder->update([
                'status' => $allReceived
                    ? PurchaseOrder::STATUS_RECEIVED
                    : ($anyReceived ? PurchaseOrder::STATUS_PARTIALLY_RECEIVED : PurchaseOrder::STATUS_OPEN),
            ]);
        });

        return response()->json(['data' => $purchaseOrder->fresh()->load(['supplier', 'creator', 'items.product'])]);
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        abort_unless($request->user()->role === 'admin' || $purchaseOrder->created_by === $request->user()->id, 403);

        if ($purchaseOrder->status === PurchaseOrder::STATUS_RECEIVED) {
            return response()->json(['message' => 'PO yang sudah diterima tidak dapat dibatalkan.'], 409);
        }

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        return response()->json(['data' => $purchaseOrder->refresh()]);
    }
}
