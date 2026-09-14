<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $sessionToken = (string) $request->session()->get('password_reset_token');
        $routeToken = (string) $request->route('token');

        if ($sessionToken === '' || $routeToken === '' || ! hash_equals($sessionToken, $routeToken)) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $sessionToken = (string) $request->session()->get('password_reset_token');
        $userId = $request->session()->get('password_reset_user_id');

        if ($sessionToken === '' || ! hash_equals($sessionToken, (string) $request->input('token')) || ! $userId) {
            return redirect()->route('password.request')->withErrors(['email' => 'جلسة إعادة التعيين غير صالحة.']);
        }

        DB::transaction(function () use ($userId, $request): void {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $user->forceFill([
                'password' => Hash::make($request->string('password')->toString()),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        $request->session()->forget(['password_reset_user_id', 'password_reset_token']);

        return redirect()->route('login')->with('status', 'تم تغيير كلمة المرور بنجاح.');
    }
}
