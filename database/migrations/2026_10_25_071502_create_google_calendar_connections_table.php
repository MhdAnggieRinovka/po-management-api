<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('google_account_email')->nullable();
            // OAuth refresh tokens must be encrypted by the application before storage.
            $table->text('refresh_token')->nullable();
            $table->string('calendar_id')->nullable();
            $table->string('sync_status', 30)->default('DISCONNECTED');
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_connections');
    }
};
