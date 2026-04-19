<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(\Illuminate\Http\Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'payroll_cycle_id' => $this->payroll_cycle_id,
            'salario_base_pagado' => (float)$this->salario_base_pagado,
            'recargos_pagados' => (float)$this->recargos_pagados,
            'deducciones' => [
                'salud' => (float)$this->deduccion_salud,
                'pension' => (float)$this->deduccion_pension,
                'total' => (float)($this->deduccion_salud + $this->deduccion_pension),
            ],
            'total_pagado' => (float)$this->total_pagado,
            'neto_pagado' => (float)$this->neto_pagado,
            'version' => $this->version,
            'snapshot' => $this->calculation_snapshot,
            'details' => PayrollDetailResource::collection($this->whenLoaded('details')),
            'created_at' => $this->created_at,
        ];
    }
}
