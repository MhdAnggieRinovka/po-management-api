<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('google_event_id')->nullable();
            $table->string('calendar_id')->nullable();
            $table->string('sync_status', 30)->default('PENDING');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['purchase_order_id', 'user_id']);
            $table->index(['user_id', 'sync_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_events');
    }
};
