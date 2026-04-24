<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'fecha_inicio' => $this->fecha_inicio->toDateTimeString(),
            'fecha_fin' => $this->fecha_fin->toDateTimeString(),
            'status' => $this->status,
            'notas' => $this->notas,
            
            // Campos dinámicos (calculados al vuelo, presentes solo en store por ahora)
            'total_hours' => $this->when(isset($this->total_hours), $this->total_hours),
            'diurnas_hours' => $this->when(isset($this->diurnas_hours), $this->diurnas_hours),
            'nocturnas_hours' => $this->when(isset($this->nocturnas_hours), $this->nocturnas_hours),

            'valor_hora' => $this->when(isset($this->valor_hora), $this->valor_hora),
            'pago_diurno' => $this->when(isset($this->pago_diurno), $this->pago_diurno),
            'pago_nocturno' => $this->when(isset($this->pago_nocturno), $this->pago_nocturno),
            'total_pago' => $this->when(isset($this->total_pago), $this->total_pago),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
