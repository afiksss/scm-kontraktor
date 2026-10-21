<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Item;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;

/**
 * Definisi deklaratif seluruh Data Master. MasterController membaca konfigurasi ini
 * untuk membuat daftar, form, validasi, dan aktivasi/nonaktif tanpa duplikasi kode.
 *
 * columns : [label, nama_atribut | Closure]
 * fields  : name, label, type (text|textarea|number|email|select), rules, options (Closure)
 */
class MasterRegistry
{
    public static function find(string $type): array
    {
        $all = self::all();
        abort_unless(isset($all[$type]), 404);

        return $all[$type] + ['key' => $type];
    }

    public static function all(): array
    {
        $activeOptions = fn (string $model, string $label = 'name') => fn () => $model::where('is_active', true)->orderBy($label)->pluck($label, 'id');

        return [
            'barang' => [
                'title' => 'Data Barang', 'singular' => 'Barang', 'model' => Item::class, 'table' => 'items', 'prefix' => 'BRG',
                'with' => ['category', 'unit'], 'search' => ['code', 'name'],
                'columns' => [
                    ['Kode', 'code'], ['Nama Barang', 'name'],
                    ['Kategori', fn ($m) => $m->category?->name],
                    ['Satuan', fn ($m) => $m->unit?->symbol],
                    ['Stok Minimum', fn ($m) => (new Fmt())->qty($m->min_stock)],
                    ['Harga Acuan', fn ($m) => (new Fmt())->money($m->price)],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama Barang', 'type' => 'text', 'rules' => 'required|string|max:150', 'span' => 2],
                    ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'rules' => 'required|exists:categories,id', 'options' => $activeOptions(Category::class)],
                    ['name' => 'unit_id', 'label' => 'Satuan', 'type' => 'select', 'rules' => 'required|exists:units,id', 'options' => $activeOptions(Unit::class)],
                    ['name' => 'min_stock', 'label' => 'Stok Minimum', 'type' => 'number', 'rules' => 'required|numeric|min:0', 'hint' => 'Notifikasi stok kritis muncul bila total stok di bawah angka ini.'],
                    ['name' => 'price', 'label' => 'Harga Acuan (Rp)', 'type' => 'number', 'rules' => 'required|numeric|min:0', 'hint' => 'Dipakai sebagai harga awal saat membuat pembelian.'],
                    ['name' => 'description', 'label' => 'Keterangan', 'type' => 'textarea', 'rules' => 'nullable|string|max:500', 'span' => 2],
                ],
            ],
            'kategori' => [
                'title' => 'Kategori Barang', 'singular' => 'Kategori', 'model' => Category::class, 'table' => 'categories', 'prefix' => 'KT',
                'with' => [], 'search' => ['code', 'name'],
                'columns' => [['Kode', 'code'], ['Nama Kategori', 'name'], ['Jumlah Barang', fn ($m) => $m->items()->count() . ' jenis']],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama Kategori', 'type' => 'text', 'rules' => 'required|string|max:100'],
                    ['name' => 'description', 'label' => 'Keterangan', 'type' => 'textarea', 'rules' => 'nullable|string|max:500'],
                ],
            ],
            'satuan' => [
                'title' => 'Satuan Barang', 'singular' => 'Satuan', 'model' => Unit::class, 'table' => 'units', 'prefix' => 'ST', 'pad' => 2,
                'with' => [], 'search' => ['code', 'name', 'symbol'],
                'columns' => [['Kode', 'code'], ['Nama Satuan', 'name'], ['Simbol', 'symbol']],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama Satuan', 'type' => 'text', 'rules' => 'required|string|max:50'],
                    ['name' => 'symbol', 'label' => 'Simbol', 'type' => 'text', 'rules' => 'required|string|max:15'],
                ],
            ],
            'supplier' => [
                'title' => 'Data Supplier', 'singular' => 'Supplier', 'model' => Supplier::class, 'table' => 'suppliers', 'prefix' => 'SUP',
                'with' => [], 'search' => ['code', 'name', 'contact_person'],
                'columns' => [['Kode', 'code'], ['Nama Supplier', 'name'], ['Kontak', 'contact_person'], ['Telepon', 'phone'], ['Email', 'email']],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama Supplier', 'type' => 'text', 'rules' => 'required|string|max:150', 'span' => 2],
                    ['name' => 'contact_person', 'label' => 'Nama Kontak', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
                    ['name' => 'phone', 'label' => 'Telepon', 'type' => 'text', 'rules' => 'nullable|string|max:30'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => 'nullable|email|max:150'],
                    ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea', 'rules' => 'nullable|string|max:500', 'span' => 2],
                ],
            ],
            'gudang' => [
                'title' => 'Data Gudang', 'singular' => 'Gudang', 'model' => Warehouse::class, 'table' => 'warehouses', 'prefix' => 'GD',
                'with' => [], 'search' => ['code', 'name', 'address'],
                'columns' => [['Kode', 'code'], ['Nama Gudang', 'name'], ['Alamat', 'address'], ['Penanggung Jawab', 'person_in_charge'],
                    ['Kapasitas', fn ($m) => $m->capacity ? number_format($m->capacity, 0, ',', '.') . ' unit' : '-']],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama Gudang', 'type' => 'text', 'rules' => 'required|string|max:150', 'span' => 2],
                    ['name' => 'address', 'label' => 'Alamat / Lokasi', 'type' => 'text', 'rules' => 'nullable|string|max:255'],
                    ['name' => 'person_in_charge', 'label' => 'Penanggung Jawab', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
                    ['name' => 'capacity', 'label' => 'Kapasitas (unit)', 'type' => 'number', 'rules' => 'nullable|integer|min:0'],
                ],
            ],
            'proyek' => [
                'title' => 'Data Proyek', 'singular' => 'Proyek', 'model' => Project::class, 'table' => 'projects', 'prefix' => 'PJK',
                'with' => [], 'search' => ['code', 'name', 'location'],
                'columns' => [['Kode', 'code'], ['Nama Proyek', 'name'], ['Lokasi', 'location'], ['Manajer Proyek', 'manager_name']],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama Proyek', 'type' => 'text', 'rules' => 'required|string|max:200', 'span' => 2],
                    ['name' => 'location', 'label' => 'Lokasi', 'type' => 'text', 'rules' => 'nullable|string|max:150'],
                    ['name' => 'manager_name', 'label' => 'Manajer Proyek', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
                ],
            ],
        ];
    }
}
