<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: felhasználó létrehozásához.
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: felhasználó létrehozásához.
     *
     * A Laravel Gate-en keresztül a UserPolicy `create` képességét ellenőrzi a User modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: felhasználó létrehozásához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists(Role::class, 'name')],
        ];
    }
}
