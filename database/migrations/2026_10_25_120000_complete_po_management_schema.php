<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compatibility migration for environments that already ran the earlier
 * scaffold-only PO migrations. New installs create the complete schema in the
 * original migrations; this migration only adds columns that are still absent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('sales')->index();
            });
        }
        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->index();
            });
        }

        if (Schema::hasTable('suppliers')) {
            if (! Schema::hasColumn('suppliers', 'supplier_code')) {
                Schema::table('suppliers', function (Blueprint $table) {
                    $table->string('supplier_code', 50)->nullable()->unique();
                });
            }
            if (! Schema::hasColumn('suppliers', 'name')) {
                Schema::table('suppliers', function (Blueprint $table) {
                    $table->string('name')->default('');
                });
            }
            if (! Schema::hasColumn('suppliers', 'email')) {
                Schema::table('suppliers', fn (Blueprint $table) => $table->string('email')->nullable());
            }
            if (! Schema::hasColumn('suppliers', 'phone')) {
                Schema::table('suppliers', fn (Blueprint $table) => $table->string('phone', 50)->nullable());
            }
            if (! Schema::hasColumn('suppliers', 'address')) {
                Schema::table('suppliers', fn (Blueprint $table) => $table->text('address')->nullable());
            }
            if (! Schema::hasColumn('suppliers', 'is_active')) {
                Schema::table('suppliers', fn (Blueprint $table) => $table->boolean('is_active')->default(true)->index());
            }
        }

        if (Schema::hasTable('products')) {
            if (! Schema::hasColumn('products', 'product_code')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->string('product_code', 50)->nullable()->unique();
                });
            }
            if (! Schema::hasColumn('products', 'name')) {
                Schema::table('products', fn (Blueprint $table) => $table->string('name')->default(''));
            }
            if (! Schema::hasColumn('products', 'unit')) {
                Schema::table('products', fn (Blueprint $table) => $table->string('unit', 30)->default('pcs'));
            }
            if (! Schema::hasColumn('products', 'description')) {
                Schema::table('products', fn (Blueprint $table) => $table->text('description')->nullable());
            }
            if (! Schema::hasColumn('products', 'is_active')) {
                Schema::table('products', fn (Blueprint $table) => $table->boolean('is_active')->default(true)->index());
            }
        }

        if (Schema::hasTable('purchase_orders')) {
            if (! Schema::hasColumn('purchase_orders', 'po_number')) {
                Schema::table('purchase_orders', function (Blueprint $table) {
                    $table->string('po_number', 60)->nullable()->unique();
                });
            }
            if (! Schema::hasColumn('purchase_orders', 'supplier_id')) {
                Schema::table('purchase_orders', function (Blueprint $table) {
                    $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
                });
            }
            if (! Schema::hasColumn('purchase_orders', 'created_by')) {
                Schema::table('purchase_orders', function (Blueprint $table) {
                    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                });
            }
            if (! Schema::hasColumn('purchase_orders', 'order_date')) {
                Schema::table('purchase_orders', fn (Blueprint $table) => $table->date('order_date')->nullable());
            }
            if (! Schema::hasColumn('purchase_orders', 'expected_delivery_date')) {
                Schema::table('purchase_orders', fn (Blueprint $table) => $table->date('expected_delivery_date')->nullable()->index());
            }
            if (! Schema::hasColumn('purchase_orders', 'status')) {
                Schema::table('purchase_orders', fn (Blueprint $table) => $table->string('status', 30)->default('OPEN')->index());
            }
            if (! Schema::hasColumn('purchase_orders', 'notes')) {
                Schema::table('purchase_orders', fn (Blueprint $table) => $table->text('notes')->nullable());
            }
        }

        if (Schema::hasTable('purchase_order_items')) {
            if (! Schema::hasColumn('purchase_order_items', 'purchase_order_id')) {
                Schema::table('purchase_order_items', function (Blueprint $table) {
                    $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                });
            }
            if (! Schema::hasColumn('purchase_order_items', 'product_id')) {
                Schema::table('purchase_order_items', function (Blueprint $table) {
                    $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                });
            }
            if (! Schema::hasColumn('purchase_order_items', 'description')) {
                Schema::table('purchase_order_items', fn (Blueprint $table) => $table->string('description')->default(''));
            }
            if (! Schema::hasColumn('purchase_order_items', 'quantity')) {
                Schema::table('purchase_order_items', fn (Blueprint $table) => $table->decimal('quantity', 15, 3)->default(0));
            }
            if (! Schema::hasColumn('purchase_order_items', 'unit_price')) {
                Schema::table('purchase_order_items', fn (Blueprint $table) => $table->decimal('unit_price', 18, 2)->default(0));
            }
            if (! Schema::hasColumn('purchase_order_items', 'received_quantity')) {
                Schema::table('purchase_order_items', fn (Blueprint $table) => $table->decimal('received_quantity', 15, 3)->default(0));
            }
        }

        if (Schema::hasTable('google_calendar_connections')) {
            if (! Schema::hasColumn('google_calendar_connections', 'user_id')) {
                Schema::table('google_calendar_connections', function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->unique()->constrained('users')->cascadeOnDelete();
                });
            }
            foreach ([
                'google_account_email' => fn (Blueprint $table) => $table->string('google_account_email')->nullable(),
                'refresh_token' => fn (Blueprint $table) => $table->text('refresh_token')->nullable(),
                'calendar_id' => fn (Blueprint $table) => $table->string('calendar_id')->nullable(),
                'sync_status' => fn (Blueprint $table) => $table->string('sync_status', 30)->default('DISCONNECTED'),
                'connected_at' => fn (Blueprint $table) => $table->timestamp('connected_at')->nullable(),
            ] as $column => $definition) {
                if (! Schema::hasColumn('google_calendar_connections', $column)) {
                    Schema::table('google_calendar_connections', $definition);
                }
            }
        }

        if (Schema::hasTable('google_calendar_events')) {
            if (! Schema::hasColumn('google_calendar_events', 'purchase_order_id')) {
                Schema::table('google_calendar_events', function (Blueprint $table) {
                    $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                });
            }
            if (! Schema::hasColumn('google_calendar_events', 'user_id')) {
                Schema::table('google_calendar_events', function (Blueprint $table) {
                    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                });
            }
            foreach ([
                'google_event_id' => fn (Blueprint $table) => $table->string('google_event_id')->nullable(),
                'calendar_id' => fn (Blueprint $table) => $table->string('calendar_id')->nullable(),
                'sync_status' => fn (Blueprint $table) => $table->string('sync_status', 30)->default('PENDING'),
                'last_synced_at' => fn (Blueprint $table) => $table->timestamp('last_synced_at')->nullable(),
                'last_error' => fn (Blueprint $table) => $table->text('last_error')->nullable(),
            ] as $column => $definition) {
                if (! Schema::hasColumn('google_calendar_events', $column)) {
                    Schema::table('google_calendar_events', $definition);
                }
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: this migration may have filled gaps
        // in an already-used database and must not remove application columns.
    }
};
