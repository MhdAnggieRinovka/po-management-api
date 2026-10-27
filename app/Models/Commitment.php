<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commitment extends Model
{
    protected $fillable = ['commitment_number','customer_name','product_id','quantity','delivery_date','status','notes','created_by','shipped_at'];

    protected function casts(): array
    {
        return ['quantity'=>'decimal:3','delivery_date'=>'date','shipped_at'=>'datetime'];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
