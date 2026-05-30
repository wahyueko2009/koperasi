<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(session('login_context') === 'anggota' ? 'member-portal' : 'dashboard');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login_context' => ['required', 'in:anggota,pengurus'],
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login_context.required' => 'Pilih dulu Anda masuk sebagai anggota atau pengurus.',
            'login_context.in' => 'Konteks login tidak valid.',
            'login.required' => 'Login wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (! Auth::attempt($request->only('login', 'password'), $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'login' => 'Login atau password tidak cocok.',
                ])
                ->onlyInput('login', 'login_context');
        }

        $request->session()->regenerate();

        $user = auth()->user()->loadMissing('official.member');

        if (! (bool) $user?->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'login' => 'User login ini sedang nonaktif dan tidak bisa masuk ke sistem.',
                ])
                ->onlyInput('login', 'login_context');
        }

        $context = $credentials['login_context'];
        $hasMemberContext = (bool) ($user->member_id || $user->official?->member_id);
        $hasOfficialContext = (bool) $user->official_id;

        if ($context === 'anggota' && ! $hasMemberContext) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'login' => 'Akun ini belum terhubung ke data anggota koperasi.',
                ])
                ->onlyInput('login', 'login_context');
        }

        if ($context === 'pengurus' && ! $hasOfficialContext) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'login' => 'Akun ini belum terdaftar sebagai pengurus koperasi.',
                ])
                ->onlyInput('login', 'login_context');
        }

        $request->session()->put('login_context', $context);

        return redirect()->intended($context === 'anggota'
            ? route('member-portal')
            : route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
