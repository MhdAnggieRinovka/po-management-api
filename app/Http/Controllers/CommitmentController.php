<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\InventoryStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommitmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Commitment::with(['product','creator'])->latest()->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'commitment_number' => ['nullable','string','max:60','unique:commitments,commitment_number'],
            'customer_name' => ['required','string','max:255'],
            'product_id' => ['required','integer','exists:products,id'],
            'quantity' => ['required','numeric','gt:0'],
            'delivery_date' => ['required','date','after_or_equal:today'],
            'notes' => ['nullable','string'],
        ]);

        $commitment = DB::transaction(function () use ($data, $request) {
            $stock = InventoryStock::firstOrCreate(['product_id' => $data['product_id']], ['quantity_on_hand' => 0]);
            $stock = InventoryStock::whereKey($stock->id)->lockForUpdate()->firstOrFail();
            $alreadyCommitted = (float) Commitment::where('product_id', $data['product_id'])->where('status','COMMITTED')->sum('quantity');
            $free = (float) $stock->quantity_on_hand - $alreadyCommitted;
            if ((float) $data['quantity'] > $free) {
                abort(422, 'Free stock tidak mencukupi. Free stock saat ini: '.max(0, $free).'.');
            }
            $data['commitment_number'] = ($data['commitment_number'] ?? null) ?: 'COM-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            return Commitment::create($data + ['status'=>'COMMITTED','created_by'=>$request->user()->id]);
        });
        return response()->json(['data'=>$commitment->load(['product','creator'])],201);
    }

    public function ship(Request $request, Commitment $commitment): JsonResponse
    {
        abort_unless(in_array($request->user()->role,['admin','warehouse'],true),403);
        if ($commitment->status !== 'COMMITTED') return response()->json(['message'=>'Hanya commitment aktif yang bisa dikirim.'],409);
        DB::transaction(function () use ($commitment) {
            $stock=InventoryStock::firstOrCreate(['product_id'=>$commitment->product_id],['quantity_on_hand'=>0]);
            $stock=InventoryStock::whereKey($stock->id)->lockForUpdate()->firstOrFail();
            if ((float)$stock->quantity_on_hand < (float)$commitment->quantity) abort(422,'Stok fisik tidak mencukupi untuk pengiriman.');
            $stock->decrement('quantity_on_hand',(float)$commitment->quantity);
            $commitment->update(['status'=>'SHIPPED','shipped_at'=>now()]);
        });
        return response()->json(['data'=>$commitment->fresh()->load(['product','creator'])]);
    }

    public function cancel(Request $request, Commitment $commitment): JsonResponse
    {
        abort_unless($request->user()->role==='admin'||($request->user()->role==='sales' && $commitment->created_by===$request->user()->id),403);
        if ($commitment->status!=='COMMITTED') return response()->json(['message'=>'Hanya commitment aktif yang bisa dibatalkan.'],409);
        $commitment->update(['status'=>'CANCELLED']);
        return response()->json(['data'=>$commitment->fresh()->load(['product','creator'])]);
    }
}
