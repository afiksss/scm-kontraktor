<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\UsageRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Support\DocumentNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Pengguna (password semua akun contoh: "password") ----
        $users = [
            ['Andi Pratama', 'andi.pratama', 'Administrator', 'admin'],
            ['Siti Rahmawati', 'siti.rahmawati', 'Staf Pembelian', 'purchasing'],
            ['Dian Putri', 'dian.putri', 'Kepala Gudang', 'warehouse'],
            ['Budi Santoso', 'budi.santoso', 'Manajer Proyek', 'project_manager'],
            ['Rudi Hartono', 'rudi.hartono', 'Direktur Operasional', 'director'],
        ];
        foreach ($users as [$name, $username, $position, $role]) {
            User::create([
                'name' => $name, 'username' => $username, 'email' => $username . '@karyabangun.co.id',
                'position' => $position, 'role' => $role, 'password' => 'password', 'is_active' => true,
            ]);
        }
        $admin = User::where('username', 'andi.pratama')->first();
        Auth::login($admin); // agar pergerakan stok awal tercatat atas nama admin

        // ---- Master ----
        $cats = [];
        foreach (['Semen', 'Besi', 'Kayu', 'Elektrikal', 'Plumbing', 'Finishing', 'Agregat', 'Peralatan'] as $name) {
            $cats[$name] = Category::create(['code' => DocumentNumber::code('KT', 'categories'), 'name' => $name]);
        }

        $units = [];
        foreach ([['Sak', 'sak'], ['Batang', 'batang'], ['Unit', 'unit'], ['Liter', 'liter'], ['Meter', 'meter'], ['Kilogram', 'kg']] as [$name, $sym]) {
            $units[$sym] = Unit::create(['code' => DocumentNumber::code('ST', 'units', 2), 'name' => $name, 'symbol' => $sym]);
        }

        $suppliers = [];
        foreach ([
            ['PT Baja Prima Nusantara', 'Hendra', '021-5550110', 'kontak1@contoh.id'],
            ['CV Sumber Material Jaya', 'Siti', '021-5550111', 'kontak2@contoh.id'],
            ['PT Cahaya Elektrik', 'Agus', '021-5550112', 'kontak3@contoh.id'],
            ['PT Beton Mandiri', 'Maya', '021-5550113', 'kontak4@contoh.id'],
        ] as [$name, $contact, $phone, $email]) {
            $suppliers[] = Supplier::create([
                'code' => DocumentNumber::code('SUP', 'suppliers'), 'name' => $name,
                'contact_person' => $contact, 'phone' => $phone, 'email' => $email,
            ]);
        }

        $warehouses = [];
        foreach ([
            ['Gudang Utama Jakarta', 'Jakarta Timur', 'Andi Pratama', 15000],
            ['Gudang Proyek Cikarang', 'Cikarang, Bekasi', 'Dian Putri', 8000],
            ['Gudang Tol Seksi 3', 'Karawang', 'Rudi Hartono', 5000],
        ] as [$name, $addr, $pic, $cap]) {
            $warehouses[] = Warehouse::create([
                'code' => DocumentNumber::code('GD', 'warehouses'), 'name' => $name,
                'address' => $addr, 'person_in_charge' => $pic, 'capacity' => $cap,
            ]);
        }

        $projects = [];
        foreach ([
            ['Proyek Gedung Perkantoran Jakarta', 'Jakarta', 'Budi Santoso'],
            ['Renovasi Pabrik Cikarang', 'Cikarang', 'Rina Maharani'],
            ['Jalan Tol Seksi 3', 'Karawang', 'Dedi Kurniawan'],
        ] as [$name, $loc, $mgr]) {
            $projects[] = Project::create([
                'code' => 'PJK-' . now()->format('Y') . '-' . str_pad((string) (count($projects) + 1), 3, '0', STR_PAD_LEFT),
                'name' => $name, 'location' => $loc, 'manager_name' => $mgr,
            ]);
        }

        // ---- Barang: [nama, kategori, satuan, minimum, harga] ----
        $goods = [
            ['Semen Portland 50 kg', 'Semen', 'sak', 50, 74000],
            ['Besi Beton 10 mm', 'Besi', 'batang', 60, 89000],
            ['Kabel NYM 2.5 mm', 'Elektrikal', 'meter', 100, 18500],
            ['Pipa PVC 4 inci', 'Plumbing', 'batang', 40, 132000],
            ['Cat Tembok Eksterior', 'Finishing', 'liter', 80, 52000],
            ['Batu Split', 'Agregat', 'kg', 500, 2600],
            ['Bor Beton', 'Peralatan', 'unit', 4, 1450000],
            ['Papan Bekisting', 'Kayu', 'unit', 30, 115000],
        ];
        $items = [];
        foreach ($goods as [$name, $cat, $unit, $min, $price]) {
            $items[] = Item::create([
                'code' => DocumentNumber::code('BRG', 'items'), 'name' => $name,
                'category_id' => $cats[$cat]->id, 'unit_id' => $units[$unit]->id,
                'min_stock' => $min, 'price' => $price,
            ]);
        }

        // ---- Stok awal lewat StockService supaya buku besar konsisten ----
        $stock = app(StockService::class);
        $opening = [ // index barang => [index gudang => jumlah]
            0 => [0 => 18], 1 => [0 => 24], 2 => [1 => 35], 3 => [0 => 180],
            4 => [1 => 260], 5 => [2 => 1840], 6 => [0 => 12], // Papan Bekisting sengaja habis
        ];
        foreach ($opening as $i => $perWarehouse) {
            foreach ($perWarehouse as $w => $qty) {
                $stock->increase($items[$i]->id, $warehouses[$w]->id, $qty, 'adjustment', null, 'SALDO-AWAL', 'Saldo awal');
            }
        }

        // ---- Contoh transaksi pembelian di berbagai status ----
        $purchaser = User::where('username', 'siti.rahmawati')->first();
        $mkPO = function (int $sup, string $status, array $lines, string $date) use ($suppliers, $items, $purchaser, $admin) {
            $po = PurchaseOrder::create([
                'number' => DocumentNumber::next('PB', 'purchase_orders'), 'date' => $date,
                'supplier_id' => $suppliers[$sup]->id, 'status' => $status, 'created_by' => $purchaser->id,
                'approved_by' => in_array($status, ['Disetujui', 'Diterima']) ? $admin->id : null,
                'approved_at' => in_array($status, ['Disetujui', 'Diterima']) ? now() : null,
            ]);
            $total = 0;
            foreach ($lines as [$i, $qty]) {
                $po->items()->create(['item_id' => $items[$i]->id, 'quantity' => $qty, 'price' => $items[$i]->price]);
                $total += $qty * $items[$i]->price;
            }
            $po->update(['total' => $total]);

            return $po;
        };

        $received = $mkPO(0, 'Disetujui', [[1, 80], [0, 40]], now()->subDays(9)->toDateString());
        $mkPO(1, 'Disetujui', [[0, 100], [5, 3000]], now()->subDays(7)->toDateString());
        $mkPO(2, 'Menunggu Persetujuan', [[2, 200]], now()->subDays(5)->toDateString());
        $mkPO(3, 'Draft', [[3, 40], [4, 60]], now()->subDays(4)->toDateString());
        $mkPO(1, 'Dibatalkan', [[4, 80]], now()->subDays(2)->toDateString());

        // Terima PO pertama secara penuh (menambah stok + buku besar).
        Auth::login(User::where('username', 'dian.putri')->first());
        $gr = GoodsReceipt::create([
            'number' => DocumentNumber::next('PN', 'goods_receipts'), 'date' => now()->subDays(8)->toDateString(),
            'purchase_order_id' => $received->id, 'warehouse_id' => $warehouses[0]->id,
            'received_by' => auth()->id(), 'delivery_note' => 'SJ-8841',
        ]);
        foreach ($received->items as $line) {
            $gr->items()->create(['item_id' => $line->item_id, 'quantity' => $line->quantity]);
            $line->update(['received_qty' => $line->quantity]);
            $stock->increase($line->item_id, $warehouses[0]->id, $line->quantity, 'purchase_receipt', $gr->id, $gr->number, 'Penerimaan ' . $received->number);
        }
        $received->update(['status' => 'Diterima']);

        // ---- Contoh permintaan barang ----
        $pm = User::where('username', 'budi.santoso')->first();
        $mkReq = function (int $proj, int $wh, string $status, array $lines, string $purpose) use ($projects, $warehouses, $items, $pm, $admin) {
            $req = UsageRequest::create([
                'number' => DocumentNumber::next('PR', 'usage_requests'), 'date' => now()->subDays(rand(1, 6))->toDateString(),
                'project_id' => $projects[$proj]->id, 'warehouse_id' => $warehouses[$wh]->id,
                'requester_id' => $pm->id, 'purpose' => $purpose, 'status' => $status,
                'approved_by' => in_array($status, ['Disetujui', 'Diproses Gudang']) ? $admin->id : null,
                'approved_at' => in_array($status, ['Disetujui', 'Diproses Gudang']) ? now() : null,
                'rejection_reason' => $status === 'Ditolak' ? 'Anggaran peralatan belum disetujui.' : null,
            ]);
            foreach ($lines as [$i, $qty]) {
                $req->items()->create([
                    'item_id' => $items[$i]->id, 'qty_requested' => $qty,
                    'qty_approved' => in_array($status, ['Disetujui', 'Diproses Gudang']) ? $qty : 0,
                ]);
            }

            return $req;
        };

        $mkReq(1, 1, 'Menunggu Persetujuan', [[4, 30]], 'Pengecatan fasad pabrik');
        $mkReq(2, 2, 'Disetujui', [[5, 150]], 'Pengerasan jalan akses');
        $mkReq(0, 0, 'Diproses Gudang', [[3, 20]], 'Instalasi plumbing lantai 3');
        $mkReq(1, 1, 'Ditolak', [[6, 2]], 'Pengeboran dinding');

        Auth::logout();
    }
}
