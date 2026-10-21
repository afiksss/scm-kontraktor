<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\Access;
use Illuminate\Http\Request;

/** Informasi hak akses (semua pengguna) dan pengaturannya (hanya yang punya izin roles.manage). */
class RoleController extends Controller
{
    public function index()
    {
        return view('roles.index', $this->data());
    }

    public function edit()
    {
        return view('roles.edit', $this->data());
    }

    public function update(Request $request)
    {
        $input = $request->input('perm', []);
        $matrix = [];
        foreach (array_keys(config('scm.permissions')) as $perm) {
            $matrix[$perm] = array_keys(array_filter((array) ($input[$perm] ?? [])));
        }

        Access::save($matrix);
        ActivityLog::record('Hak Akses', 'Mengubah pengaturan hak akses role');

        return redirect()->route('roles.index')->with('success', 'Pengaturan hak akses disimpan dan langsung berlaku.');
    }

    public function reset()
    {
        Access::reset();
        ActivityLog::record('Hak Akses', 'Mengembalikan hak akses ke pengaturan bawaan');

        return redirect()->route('roles.index')->with('success', 'Hak akses dikembalikan ke pengaturan bawaan.');
    }

    private function data(): array
    {
        $map = Access::map();
        $matrix = collect(config('scm.permission_labels'))
            ->map(fn ($meta, $perm) => ['key' => $perm, 'label' => $meta[0], 'group' => $meta[1], 'roles' => $map[$perm] ?? []])
            ->groupBy('group');

        return [
            'roles' => config('scm.roles'),
            'info' => config('scm.role_info'),
            'matrix' => $matrix,
            'mine' => auth()->user()->role,
            'locked' => config('scm.locked_for_admin', []),
        ];
    }
}
