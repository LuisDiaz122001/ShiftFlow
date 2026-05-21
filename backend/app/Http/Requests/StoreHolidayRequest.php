<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date', 'unique:holidays,fecha'],
            'nombre' => ['required', 'string', 'max:255'],
        ];
    }
}
