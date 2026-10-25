<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Product::query()->orderBy('name')->paginate(20)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_code' => ['required', 'string', 'max:50', 'unique:products,product_code'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return response()->json(['data' => Product::create($data)], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json(['data' => $product]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'product_code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('products', 'product_code')->ignore($product->id)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'unit' => ['sometimes', 'required', 'string', 'max:30'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product->update($data);

        return response()->json(['data' => $product->refresh()]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->purchaseOrderItems()->exists()) {
            return response()->json(['message' => 'Produk sudah digunakan pada detail PO dan tidak dapat dihapus.'], 409);
        }

        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}
