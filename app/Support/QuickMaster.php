<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * "Tambah data cepat": membuat data master langsung dari form transaksi, sehingga
 * pengguna tidak perlu mengisi Data Master terlebih dahulu.
 *
 * Aturan:
 *  - Dicocokkan dengan nama (tanpa membedakan huruf besar/kecil) agar tidak terjadi duplikat.
 *    Bila sudah ada, data lama yang dipakai.
 *  - Bila data lama berstatus nonaktif, ditolak dengan pesan jelas (hanya admin yang boleh mengaktifkan).
 *  - Hanya role yang punya izin quick.* yang boleh membuat data baru.
 *  - Data yang dibuat diberi isian standar; admin dapat melengkapinya kapan saja di Data Master.
 */
class QuickMaster
{
    public const DEFAULT_CATEGORY = 'Umum';

    public function supplier(string $name, string $field = 'new_supplier'): Supplier
    {
        return $this->findOrCreate(Supplier::class, 'quick.supplier', $name, $field, 'supplier', 'SUP', 3, []);
    }

    public function warehouse(string $name, string $field = 'new_warehouse'): Warehouse
    {
        return $this->findOrCreate(Warehouse::class, 'quick.warehouse', $name, $field, 'gudang', 'GD', 3, []);
    }

    public function project(string $name, string $field = 'new_project'): Project
    {
        return $this->findOrCreate(Project::class, 'quick.project', $name, $field, 'proyek', 'PJK', 3, []);
    }

    /** Barang baru: kategori default "Umum", stok minimum 0, harga acuan = harga di transaksi. */
    public function item(string $name, ?string $unitText, float $price = 0, string $field = 'items'): Item
    {
        $name = $this->clean($name);
        $existing = Item::whereRaw('lower(name) = ?', [mb_strtolower($name)])->first();

        if ($existing) {
            if (!$existing->is_active) {
                $this->fail($field, "Barang \"{$existing->name}\" sudah ada tetapi nonaktif. Minta admin mengaktifkannya.");
            }

            return $existing;
        }

        $this->authorize('quick.item', $field);
        if (blank($unitText)) {
            $this->fail($field, "Barang \"{$name}\" belum ada di daftar. Isi satuannya agar bisa ditambahkan sebagai barang baru.");
        }

        $item = Item::create([
            'code' => DocumentNumber::code('BRG', 'items'),
            'name' => $name,
            'category_id' => $this->category(self::DEFAULT_CATEGORY)->id,
            'unit_id' => $this->unit($unitText, $field)->id,
            'min_stock' => 0,
            'price' => max($price, 0),
            'description' => 'Dibuat cepat dari transaksi; lengkapi kategori & stok minimum di Data Master.',
            'is_active' => true,
        ]);
        ActivityLog::record('Data Master', "Menambah cepat Barang {$item->code} - {$item->name}");

        return $item;
    }

    /** Satuan dicari dari simbol atau nama; dibuat otomatis bila belum ada. */
    public function unit(string $text, string $field = 'items'): Unit
    {
        $text = $this->clean($text);
        $lower = mb_strtolower($text);

        $unit = Unit::whereRaw('lower(symbol) = ?', [$lower])->orWhereRaw('lower(name) = ?', [$lower])->first();
        if ($unit) {
            return $unit;
        }

        $unit = Unit::create([
            'code' => DocumentNumber::code('ST', 'units', 2),
            'name' => mb_convert_case($text, MB_CASE_TITLE),
            'symbol' => $lower,
            'is_active' => true,
        ]);
        ActivityLog::record('Data Master', "Menambah cepat Satuan {$unit->code} - {$unit->name}");

        return $unit;
    }

    public function category(string $name): Category
    {
        $name = $this->clean($name);
        $cat = Category::whereRaw('lower(name) = ?', [mb_strtolower($name)])->first();

        return $cat ?: Category::create([
            'code' => DocumentNumber::code('KT', 'categories'),
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function findOrCreate(string $model, string $permission, string $name, string $field, string $singularKey, string $prefix, int $pad, array $extra): Model
    {
        $name = $this->clean($name);
        $table = (new $model)->getTable();
        $existing = $model::whereRaw('lower(name) = ?', [mb_strtolower($name)])->first();

        if ($existing) {
            if (!$existing->is_active) {
                $this->fail($field, "\"{$existing->name}\" sudah ada tetapi nonaktif. Minta admin mengaktifkannya.");
            }

            return $existing;
        }

        $this->authorize($permission, $field);

        $row = $model::create($extra + [
            'code' => DocumentNumber::code($prefix, $table, $pad),
            'name' => $name,
            'is_active' => true,
        ]);
        ActivityLog::record('Data Master', "Menambah cepat {$singularKey} {$row->code} - {$row->name}");

        return $row;
    }

    private function authorize(string $permission, string $field): void
    {
        if (!Gate::allows($permission)) {
            $this->fail($field, 'Role Anda tidak boleh menambah data baru di sini. Pilih dari daftar atau minta admin menambahkannya.');
        }
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
