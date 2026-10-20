<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('profile.edit')
            : redirect()->route('profile.edit')->with('status', __('Please verify your email address to continue.'));
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('profile.edit')->with('status', __('Your email address has been verified. Thank you!'));
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('status', __('Your email is already verified.'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', __('A new verification link has been sent to :email.', ['email' => $request->user()->email]));
    }
}
