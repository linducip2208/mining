<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileSecurityController extends Controller
{
    public function password()
    {
        return view('profile.security');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed|different:current_password',
        ]);

        $user = $request->user();
        $user->update([
            'password' => bcrypt($validated['password']),
            'password_changed_at' => now(),
            'force_password_reset' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Password berhasil diubah.');
    }
}
