<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\DocumentNumber;
use App\Support\MasterRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterController extends Controller
{
    public function index(Request $request, string $type)
    {
        $cfg = MasterRegistry::find($type);
        $query = $cfg['model']::query()->with($cfg['with']);

        if ($s = trim((string) $request->query('q'))) {
            $query->where(function ($w) use ($cfg, $s) {
                foreach ($cfg['search'] as $col) {
                    $w->orWhere($col, 'like', "%{$s}%");
                }
            });
        }

        match ($request->query('status')) {
            'Aktif' => $query->where('is_active', true),
            'Nonaktif' => $query->where('is_active', false),
            default => null,
        };

        return view('master.index', [
            'cfg' => $cfg,
            'type' => $type,
            'rows' => $query->orderBy('code')->paginate(10)->withQueryString(),
        ]);
    }

    public function create(string $type)
    {
        return view('master.form', ['cfg' => MasterRegistry::find($type), 'type' => $type, 'row' => null]);
    }

    public function store(Request $request, string $type)
    {
        $cfg = MasterRegistry::find($type);
        $data = $request->validate($this->rules($cfg));
        $data['code'] = DocumentNumber::code($cfg['prefix'], $cfg['table'], $cfg['pad'] ?? 3);

        $row = $cfg['model']::create($data);
        ActivityLog::record('Data Master', "Menambah {$cfg['singular']} {$row->code} - {$row->name}");

        return redirect()->route('master.index', $type)->with('success', "{$cfg['singular']} {$row->name} berhasil ditambahkan.");
    }

    public function edit(string $type, int $id)
    {
        $cfg = MasterRegistry::find($type);

        return view('master.form', ['cfg' => $cfg, 'type' => $type, 'row' => $cfg['model']::findOrFail($id)]);
    }

    public function update(Request $request, string $type, int $id)
    {
        $cfg = MasterRegistry::find($type);
        $row = $cfg['model']::findOrFail($id);
        $row->update($request->validate($this->rules($cfg, $row->id)));
        ActivityLog::record('Data Master', "Mengubah {$cfg['singular']} {$row->code} - {$row->name}");

        return redirect()->route('master.index', $type)->with('success', "{$cfg['singular']} {$row->name} berhasil diperbarui.");
    }

    /** Data master yang sudah dipakai transaksi dinonaktifkan, tidak dihapus. */
    public function toggle(string $type, int $id)
    {
        $cfg = MasterRegistry::find($type);
        $row = $cfg['model']::findOrFail($id);
        $row->update(['is_active' => !$row->is_active]);

        $state = $row->is_active ? 'diaktifkan' : 'dinonaktifkan';
        ActivityLog::record('Data Master', ucfirst($state) . " {$cfg['singular']} {$row->code} - {$row->name}");

        return back()->with('success', "{$cfg['singular']} {$row->name} berhasil {$state}.");
    }

    private function rules(array $cfg, ?int $ignoreId = null): array
    {
        $rules = [];
        foreach ($cfg['fields'] as $f) {
            $rules[$f['name']] = $f['rules'];
        }
        $rules['name'] = ['required', 'string', 'max:200', Rule::unique($cfg['table'], 'name')->ignore($ignoreId)];

        return $rules;
    }
}
