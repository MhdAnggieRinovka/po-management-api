<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('quantity_on_hand', 14, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('commitments', function (Blueprint $table) {
            $table->id();
            $table->string('commitment_number')->unique();
            $table->string('customer_name');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->date('delivery_date');
            $table->string('status')->default('COMMITTED');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();
        });

        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->string('sample_number')->unique();
            $table->string('customer_name');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->date('sample_date');
            $table->string('status')->default('PLANNED');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
        Schema::dropIfExists('commitments');
        Schema::dropIfExists('inventory_stocks');
    }
};
