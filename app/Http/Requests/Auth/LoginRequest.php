<?php

namespace App\Http\Requests\Auth;

use App\Models\LoginHistory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials (email OR username),
     * with account status checks, lockout and login history audit.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim($this->string('email'));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $login)->first();

        $fail = function (string $msg = 'Kredensial tidak cocok dengan catatan kami.') {
            LoginHistory::create(['user_id' => $this->userAttemptId(), 'event' => 'FAILED', 'ip_address' => $this->ip(), 'user_agent' => substr((string) $this->userAgent(), 0, 255)]);
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => $msg,
            ]);
        };

        if (! $user) {
            $fail();
        }

        if ($user->status === 'LOCKED') {
            $fail('Akun terkunci. Hubungi administrator.');
        }

        if ($user->status !== 'ACTIVE') {
            $fail('Akun tidak aktif. Hubungi administrator.');
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            $fail('Akun terkunci sementara. Coba lagi nanti.');
        }

        if (! Auth::attempt([$field => $login, 'password' => $this->password], $this->boolean('remember'))) {
            $user->increment('failed_login_count');

            // auto lockout after 5 failed attempts
            $maxAttempts = max(1, (int) Setting::get('security.max_login_attempts', 5));
            $lockoutMinutes = max(1, (int) Setting::get('security.lockout_minutes', 30));
            if ($user->failed_login_count >= $maxAttempts) {
                $user->update(['status' => 'LOCKED', 'locked_until' => now()->addMinutes($lockoutMinutes)]);
                LoginHistory::create(['user_id' => $user->id, 'event' => 'LOCKOUT', 'ip_address' => $this->ip(), 'user_agent' => substr((string) $this->userAgent(), 0, 255)]);
                $fail('Terlalu banyak percobaan gagal. Akun terkunci '.$lockoutMinutes.' menit.');
            }

            $fail();
        }

        if (! empty($this->password) && $this->boolean('remember') === false) {
            // ok
        }

        RateLimiter::clear($this->throttleKey());

        // success: reset counters + audit login
        $user->update([
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $this->ip(),
        ]);
        LoginHistory::create(['user_id' => $user->id, 'event' => 'LOGIN', 'ip_address' => $this->ip(), 'user_agent' => substr((string) $this->userAgent(), 0, 255)]);
    }

    protected function userAttemptId(): ?int
    {
        $login = trim($this->string('email'));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return User::where($field, $login)->value('id');
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $maxAttempts = max(1, (int) Setting::get('security.max_login_attempts', 5));
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
