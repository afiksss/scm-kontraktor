<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'tab' => $request->query('tab', 'profil'),
            'logs' => ActivityLog::where('user_id', $request->user()->id)->latest('id')->limit(30)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'position' => 'nullable|string|max:100',
        ]);
        $user->update($data);
        ActivityLog::record('Profil', 'Memperbarui profil');

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil disimpan.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ], ['password.different' => 'Password baru harus berbeda dari password saat ini.'], ['current_password' => 'password saat ini', 'password' => 'password baru']);

        if (!Hash::check($data['current_password'], $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }

        $request->user()->update(['password' => $data['password']]);
        ActivityLog::record('Profil', 'Mengubah password');

        return redirect()->route('profile.edit', ['tab' => 'password'])->with('success', 'Password berhasil diubah.');
    }
}
