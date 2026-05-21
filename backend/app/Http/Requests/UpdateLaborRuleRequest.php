<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLaborRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $laborRuleId = $this->route('laborRule')?->id ?? $this->route('labor_rule')?->id;

        return [
            'vigente_desde' => [
                'required',
                'date',
                Rule::unique('labor_rules', 'vigente_desde')->ignore($laborRuleId),
            ],
            'hora_diurna_inicio' => ['required', 'date_format:H:i'],
            'hora_nocturna_inicio' => ['required', 'date_format:H:i'],
            'recargo_nocturno' => ['required', 'numeric', 'min:0', 'max:500'],
            'recargo_dominical' => ['required', 'numeric', 'min:0', 'max:500'],
            'extra_diurna' => ['required', 'numeric', 'min:0', 'max:500'],
            'extra_nocturna' => ['required', 'numeric', 'min:0', 'max:500'],
            'porcentaje_salud' => ['required', 'numeric', 'min:0', 'max:100'],
            'porcentaje_pension' => ['required', 'numeric', 'min:0', 'max:100'],
            'horas_max_diarias' => ['required', 'numeric', 'min:1', 'max:24'],
        ];
    }
}
