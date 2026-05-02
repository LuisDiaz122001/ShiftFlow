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
        $totalHours = (float) $this->total_hours;
        $diurnasHours = (float) $this->diurnas_hours;
        $nocturnasHours = (float) $this->nocturnas_hours;
        $totalPago = (float) $this->total_pago;

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'fecha_inicio' => $this->fecha_inicio->toDateTimeString(),
            'fecha_fin' => $this->fecha_fin->toDateTimeString(),
            'status' => $this->status,
            'notas' => $this->notas,
            
            // Campos del modelo (Source of Truth)
            'total_hours' => $totalHours,
            'diurnas_hours' => $diurnasHours,
            'nocturnas_hours' => $nocturnasHours,
            'total_pago' => $totalPago,
            'calculation' => [
                'total_hours' => $totalHours,
                'total_pay' => $totalPago,
                'breakdown' => [
                    [
                        'label' => 'diurnas',
                        'hours' => $diurnasHours,
                    ],
                    [
                        'label' => 'nocturnas',
                        'hours' => $nocturnasHours,
                    ],
                ],
            ],

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
