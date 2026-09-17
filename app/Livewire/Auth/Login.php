<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Masuk ke Sistem - CPS ERA')]
class Login extends Component
{
    #[Validate('required|string', message: [
        'required' => 'Email Pegawai wajib diisi.',
    ])]
    public string $login = '';

    #[Validate('required|string', message: [
        'required' => 'Password wajib diisi.',
    ])]
    public string $password = '';

    public bool $remember = false;

    /**
     * Real-time validation saat properti berubah di form.
     */
    public function updated(string $propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    /**
     * Proses autentikasi user dan redirect berbasis role (RBAC).
     */
    public function authenticate()
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        $loginField = filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'employee_id';

        $credentials = [
            $loginField => $this->login,
            'password' => $this->password,
        ];

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        // Redirect sesuai role:
        // Admin -> /admin (Filament Master Data)
        // Employee/Supervisor/Quality -> /dashboard (CPS ERA Hub)
        $destination = $user->hasRole('admin') ? '/admin' : route('dashboard', absolute: false);

        return redirect()->intended($destination);
    }

    /**
     * Proteksi rate limiter brute force.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Throttle key unik per input & IP.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->login).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
