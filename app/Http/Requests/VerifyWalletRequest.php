<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_code' => ['required', 'string', 'size:2'],
            'account_number' => ['required', 'string', 'min:6', 'max:20'],
        ];
    }
}
