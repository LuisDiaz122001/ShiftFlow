<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $holidayId = $this->route('holiday')?->id;

        return [
            'fecha' => [
                'required',
                'date',
                Rule::unique('holidays', 'fecha')->ignore($holidayId),
            ],
            'nombre' => ['required', 'string', 'max:255'],
        ];
    }
}
