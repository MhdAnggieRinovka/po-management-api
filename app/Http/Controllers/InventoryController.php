<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\InventoryStock;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()->where('is_active', true)->orderBy('name')->get();
        $rows = $products->map(function (Product $product) {
            $stock = InventoryStock::firstOrCreate(['product_id' => $product->id], ['quantity_on_hand' => 0]);
            $committed = (float) Commitment::where('product_id', $product->id)->where('status', 'COMMITTED')->sum('quantity');
            $onHand = (float) $stock->quantity_on_hand;
            return [
                'product_id' => $product->id,
                'product_code' => $product->product_code,
                'product_name' => $product->name,
                'unit' => $product->unit,
                'quantity_on_hand' => $onHand,
                'committed_quantity' => $committed,
                'free_stock' => max(0, $onHand - $committed),
            ];
        });
        return response()->json(['data' => $rows]);
    }

    public static function increaseForProduct(?int $productId, float $quantity): void
    {
        if (!$productId || $quantity <= 0) return;
        $stock = InventoryStock::firstOrCreate(['product_id' => $productId], ['quantity_on_hand' => 0]);
        $stock->increment('quantity_on_hand', $quantity);
    }
}
