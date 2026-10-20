<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'alpha_dash:ascii', 'unique:users,username'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => strip_tags($validated['name']),
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('status', __('Your account is ready. We sent a verification link to :email.', ['email' => $user->email]));
    }
}
