<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>|string>
     */
    public function rules(): array
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');

        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'documento' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('employees', 'documento')->ignore($employee?->id),
            ],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:255'],
            'salario_base' => ['sometimes', 'required', 'numeric', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee?->user_id),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }
}
