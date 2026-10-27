<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sample extends Model
{
    protected $fillable = ['sample_number','customer_name','product_id','quantity','sample_date','status','notes','created_by','issued_at'];

    protected function casts(): array
    {
        return ['quantity'=>'decimal:3','sample_date'=>'date','issued_at'=>'datetime'];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
