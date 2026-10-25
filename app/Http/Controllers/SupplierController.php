<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Supplier::query()->orderBy('name')->paginate(20)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_code' => ['required', 'string', 'max:50', 'unique:suppliers,supplier_code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return response()->json(['data' => Supplier::create($data)], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json(['data' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $data = $request->validate([
            'supplier_code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('suppliers', 'supplier_code')->ignore($supplier->id)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $supplier->update($data);

        return response()->json(['data' => $supplier->refresh()]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($supplier->purchaseOrders()->exists()) {
            return response()->json(['message' => 'Supplier sudah digunakan pada PO dan tidak dapat dihapus.'], 409);
        }

        $supplier->delete();

        return response()->json(['message' => 'Supplier berhasil dihapus.']);
    }
}
