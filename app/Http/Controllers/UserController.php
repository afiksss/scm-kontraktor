<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($s = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('username', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }
        match ($request->query('status')) {
            'Aktif' => $query->where('is_active', true),
            'Nonaktif' => $query->where('is_active', false),
            default => null,
        };

        return view('users.index', ['users' => $query->orderBy('name')->paginate(10)->withQueryString()]);
    }

    public function create()
    {
        return view('users.form', ['user' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + ['password' => 'required|string|min:8|confirmed']);
        $user = User::create($data + ['is_active' => true]);
        ActivityLog::record('Pengguna', "Menambah pengguna {$user->username} ({$user->roleLabel()})");

        return redirect()->route('users.index')->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate($this->rules($user->id) + ['password' => 'nullable|string|min:8|confirmed']);

        if (empty($data['password'])) {
            unset($data['password']);
        }
        // Admin tidak boleh menurunkan role dirinya sendiri (mencegah terkunci dari sistem).
        if ($user->id === auth()->id()) {
            $data['role'] = $user->role;
        }

        $user->update($data);
        ActivityLog::record('Pengguna', "Mengubah pengguna {$user->username}");

        return redirect()->route('users.index')->with('success', "Pengguna {$user->name} berhasil diperbarui.");
    }

    public function toggle(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }
        $user->update(['is_active' => !$user->is_active]);
        ActivityLog::record('Pengguna', ($user->is_active ? 'Mengaktifkan' : 'Menonaktifkan') . " pengguna {$user->username}");

        return back()->with('success', 'Status pengguna diperbarui.');
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'name' => 'required|string|max:100',
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($ignoreId)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($ignoreId)],
            'position' => 'required|string|max:100',
            'role' => ['required', Rule::in(array_keys(config('scm.roles')))],
        ];
    }
}
