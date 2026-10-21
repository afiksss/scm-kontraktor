<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->date('date');
            $t->foreignId('supplier_id')->constrained('suppliers');
            $t->string('status')->default('Draft')->index();
            $t->decimal('total', 15, 2)->default(0);
            $t->text('notes')->nullable();
            $t->string('rejection_reason')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('items');
            $t->decimal('quantity', 14, 2);
            $t->decimal('price', 15, 2)->default(0);
            $t->decimal('received_qty', 14, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->date('date');
            $t->foreignId('purchase_order_id')->constrained('purchase_orders');
            $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('delivery_note')->nullable(); // nomor surat jalan supplier
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('goods_receipt_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('items');
            $t->decimal('quantity', 14, 2);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['goods_receipt_items', 'goods_receipts', 'purchase_order_items', 'purchase_orders'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
