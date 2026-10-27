<?php

namespace App\Http\Controllers;

use App\Models\InventoryStock;
use App\Models\Sample;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SampleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data'=>Sample::with(['product','creator'])->latest()->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data=$request->validate([
            'sample_number'=>['nullable','string','max:60','unique:samples,sample_number'],
            'customer_name'=>['required','string','max:255'],
            'product_id'=>['required','integer','exists:products,id'],
            'quantity'=>['required','numeric','gt:0'],
            'sample_date'=>['required','date'],
            'notes'=>['nullable','string'],
        ]);
        $sample=Sample::create($data+[
            'sample_number'=>$data['sample_number'] ?: 'SMP-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'status'=>'PLANNED',
            'created_by'=>$request->user()->id,
        ]);
        return response()->json(['data'=>$sample->load(['product','creator'])],201);
    }

    public function issue(Request $request, Sample $sample): JsonResponse
    {
        abort_unless(in_array($request->user()->role,['admin','warehouse'],true),403);
        if ($sample->status!=='PLANNED') return response()->json(['message'=>'Sample ini bukan dalam status menunggu dikeluarkan.'],409);
        DB::transaction(function () use ($sample) {
            $stock=InventoryStock::where('product_id',$sample->product_id)->lockForUpdate()->firstOrFail();
            if ((float)$stock->quantity_on_hand < (float)$sample->quantity) abort(422,'Stok fisik tidak mencukupi untuk mengeluarkan sample.');
            $stock->decrement('quantity_on_hand',(float)$sample->quantity);
            $sample->update(['status'=>'ISSUED','issued_at'=>now()]);
        });
        return response()->json(['data'=>$sample->fresh()->load(['product','creator'])]);
    }

    public function cancel(Request $request, Sample $sample): JsonResponse
    {
        abort_unless($request->user()->role==='admin'||($request->user()->role==='sales' && $sample->created_by===$request->user()->id),403);
        if ($sample->status!=='PLANNED') return response()->json(['message'=>'Hanya sample yang belum dikeluarkan yang bisa dibatalkan.'],409);
        $sample->update(['status'=>'CANCELLED']);
        return response()->json(['data'=>$sample->fresh()->load(['product','creator'])]);
    }
}
