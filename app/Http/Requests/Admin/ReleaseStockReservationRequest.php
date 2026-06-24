<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReleaseStockReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('release', $this->route('stockReservation'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
