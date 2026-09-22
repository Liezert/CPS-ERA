<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Ganti kata sandi sementara (akun buatan admin) menjadi kata sandi pribadi.
 */
class ForcePasswordChangeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.current_password' => 'Kata sandi sementara tidak sesuai.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi sementara.',
        ]);

        $request->user()->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
