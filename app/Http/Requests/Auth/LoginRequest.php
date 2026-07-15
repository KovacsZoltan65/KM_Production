<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: bejelentkezés.
 */
class LoginRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: bejelentkezés.
     *
     * A kéréshez nem végez külön jogosultsági ellenőrzést; minden elérő felhasználót engedélyez.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: bejelentkezés.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Megkísérli a hitelesítést a validált bejelentkezési adatokkal és kezeli a próbálkozásszámlálót.
     *
     * @throws ValidationException Sikertelen hitelesítés esetén.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Elutasítja a bejelentkezést, ha az e-mail- és IP-alapú próbálkozási korlát betelt.
     *
     * @throws ValidationException Túllépett próbálkozási korlát esetén.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
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
     * Előállítja a normalizált e-mail-címből és IP-címből képzett korlátozási kulcsot.
     *
     * @return non-empty-string
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
