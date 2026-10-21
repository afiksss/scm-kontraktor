<?php

namespace App\Support;

use App\Models\RolePermission;
use Illuminate\Support\Facades\DB;

/**
 * Sumber tunggal hak akses. Nilai bawaan dari config/scm.php; bila admin menyimpan
 * pengaturan, nilai di tabel role_permissions yang dipakai. Aman bila tabel belum dimigrasi.
 */
class Access
{
    private static ?array $map = null;

    public static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $map = config('scm.permissions');
        try {
            foreach (RolePermission::all() as $row) {
                if (array_key_exists($row->permission, $map)) {
                    $map[$row->permission] = (array) $row->roles;
                }
            }
        } catch (\Throwable $e) {
            // Tabel belum ada (migrasi belum dijalankan): pakai bawaan config.
        }

        return self::$map = self::lockAdmin($map);
    }

    public static function roles(string $permission): array
    {
        return self::map()[$permission] ?? [];
    }

    /** Simpan matriks baru: [izin => [role,...]]. */
    public static function save(array $matrix): void
    {
        $valid = array_keys(config('scm.roles'));
        $matrix = self::lockAdmin($matrix);

        DB::transaction(function () use ($matrix, $valid) {
            foreach (array_keys(config('scm.permissions')) as $perm) {
                $roles = array_values(array_intersect($valid, (array) ($matrix[$perm] ?? [])));
                RolePermission::updateOrCreate(['permission' => $perm], ['roles' => $roles]);
            }
        });
        self::$map = null;
    }

    public static function reset(): void
    {
        RolePermission::query()->delete();
        self::$map = null;
    }

    private static function lockAdmin(array $map): array
    {
        foreach (config('scm.locked_for_admin', []) as $perm) {
            $map[$perm] = array_values(array_unique(array_merge(['admin'], (array) ($map[$perm] ?? []))));
        }

        return $map;
    }
}
