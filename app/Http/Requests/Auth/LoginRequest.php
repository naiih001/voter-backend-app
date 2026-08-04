<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Maximum failed attempts before an account is locked.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * How long (in minutes) an account stays locked once tripped.
     */
    public const LOCKOUT_MINUTES = 15;

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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Combines IP-based rate limiting with per-account lockout: after
     * self::MAX_ATTEMPTS failed logins the account is locked for
     * self::LOCKOUT_MINUTES, regardless of the source IP.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = User::where('email', $this->input('email'))->first();

        // Reject early if the account is currently locked.
        if ($user && $user->isLocked()) {
            throw ValidationException::withMessages([
                'email' => $this->lockoutMessage($user->locked_until),
            ]);
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            $this->recordFailedAttempt($user);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Successful login: clear throttle + reset the account's failure state.
        RateLimiter::clear($this->throttleKey());
        $this->resetLockout($user);
    }

    /**
     * Increment the failed-attempt counter and lock the account if the
     * threshold is reached.
     */
    protected function recordFailedAttempt(?User $user): void
    {
        if (! $user) {
            return;
        }

        $user->increment('failed_login_attempts');

        if ($user->failed_login_attempts >= self::MAX_ATTEMPTS) {
            $user->forceFill([
                'locked_until' => Carbon::now()->addMinutes(self::LOCKOUT_MINUTES),
            ])->save();

            event(new Lockout($this));
        }
    }

    /**
     * Clear the failure state after a successful login.
     */
    protected function resetLockout(?User $user): void
    {
        if ($user && ($user->failed_login_attempts > 0 || $user->locked_until !== null)) {
            $user->forceFill([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ])->save();
        }
    }

    /**
     * Build a human-friendly "account locked" message.
     */
    protected function lockoutMessage(Carbon $lockedUntil): string
    {
        $minutes = max(1, (int) ceil(Carbon::now()->diffInSeconds($lockedUntil, false) / 60));

        return "Account locked due to too many failed login attempts. Try again in {$minutes} minute(s).";
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
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
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}
