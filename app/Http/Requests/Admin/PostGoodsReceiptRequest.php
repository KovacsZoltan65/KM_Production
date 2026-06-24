<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PostGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('post', $this->route('goodsReceipt'));
    }

    public function rules(): array
    {
        return [];
    }
}
