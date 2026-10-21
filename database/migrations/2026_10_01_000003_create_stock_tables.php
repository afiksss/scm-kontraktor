<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Saldo stok per barang per gudang.
        Schema::create('stocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('items');
            $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->decimal('quantity', 14, 2)->default(0);
            $t->timestamps();
            $t->unique(['item_id', 'warehouse_id']);
        });

        // Buku besar pergerakan stok (append-only).
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('items');
            $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->string('type'); // in, out, transfer_in, transfer_out, adjustment
            $t->decimal('qty_in', 14, 2)->default(0);
            $t->decimal('qty_out', 14, 2)->default(0);
            $t->decimal('balance', 14, 2)->default(0); // saldo gudang setelah transaksi
            $t->string('reference_type')->nullable();
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->string('reference_number')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('note')->nullable();
            $t->timestamps();
            $t->index(['item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stocks');
    }
};
