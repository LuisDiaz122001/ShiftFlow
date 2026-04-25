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
            
            // Campos del modelo (Source of Truth)
            'total_hours' => (float) $this->total_hours,
            'diurnas_hours' => (float) $this->diurnas_hours,
            'nocturnas_hours' => (float) $this->nocturnas_hours,
            'total_pago' => (float) $this->total_pago,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
