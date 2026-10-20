<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $paid = $user->donations()->where('status', Donation::STATUS_PAID);

        return view('profile', [
            'user' => $user,
            'stats' => [
                'total' => (int) (clone $paid)->sum('amount'),
                'count' => (clone $paid)->count(),
                'prayers' => $user->comments()->count(),
                'streak' => $user->kindnessStreak(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['username' => Str::lower(trim((string) $request->input('username')))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($user->id)],
            'date_of_birth' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{8,20}$/'],
        ], [
            'phone.regex' => __('Please enter a valid phone number.'),
        ]);

        $validated['name'] = strip_tags($validated['name']);
        $user->update($validated);

        return back()->with('status', __('Your profile has been updated.'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ]);

        $request->user()->update(['password' => $validated['password']]);
        $request->session()->regenerate();

        return back()->with('status', __('Your password has been changed.'));
    }
}
