<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_requests', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->date('date');
            $t->foreignId('project_id')->constrained('projects');
            $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('purpose');
            $t->string('status')->default('Menunggu Persetujuan')->index();
            $t->text('notes')->nullable();
            $t->string('rejection_reason')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('released_at')->nullable();
            $t->timestamps();
        });

        Schema::create('usage_request_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('usage_request_id')->constrained('usage_requests')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('items');
            $t->decimal('qty_requested', 14, 2);
            $t->decimal('qty_approved', 14, 2)->default(0);
            $t->decimal('qty_released', 14, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->date('date');
            $t->foreignId('from_warehouse_id')->constrained('warehouses');
            $t->foreignId('to_warehouse_id')->constrained('warehouses');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('items');
            $t->decimal('quantity', 14, 2);
            $t->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('module');
            $t->string('description');
            $t->string('ip_address', 45)->nullable();
            $t->timestamps();
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'stock_transfer_items', 'stock_transfers', 'usage_request_items', 'usage_requests'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
