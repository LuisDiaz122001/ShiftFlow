<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'fecha_inicio' => ['required', 'date', 'date_format:Y-m-d H:i:s'],
            'fecha_fin' => ['required', 'date', 'date_format:Y-m-d H:i:s', 'after:fecha_inicio'],
            'notas' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'El empleado es obligatorio.',
            'employee_id.exists' => 'El empleado seleccionado no es válido.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date_format' => 'El formato de fecha de inicio debe ser AAAA-MM-DD HH:MM:SS.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date_format' => 'El formato de fecha de fin debe ser AAAA-MM-DD HH:MM:SS.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }
}
