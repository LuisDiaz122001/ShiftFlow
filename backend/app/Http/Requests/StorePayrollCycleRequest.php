<?php

namespace App\Http\Requests;

use App\Models\PayrollCycle;
use Illuminate\Foundation\Http\FormRequest;

class StorePayrollCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'fecha_pago' => ['required', 'date', 'after_or_equal:fecha_fin'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $inicio = $this->input('fecha_inicio');
            $fin = $this->input('fecha_fin');

            $exactDuplicate = PayrollCycle::query()
                ->where('fecha_inicio', $inicio)
                ->where('fecha_fin', $fin)
                ->exists();

            if ($exactDuplicate) {
                $validator->errors()->add('fecha_inicio', 'Ya existe un periodo con el mismo rango de fechas.');
                return;
            }

            $overlap = PayrollCycle::query()
                ->where('fecha_inicio', '<=', $fin)
                ->where('fecha_fin', '>=', $inicio)
                ->exists();

            if ($overlap) {
                $validator->errors()->add('fecha_inicio', 'El rango de fechas se solapa con otro periodo existente.');
            }
        });
    }
}
