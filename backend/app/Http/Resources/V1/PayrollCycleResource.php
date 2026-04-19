<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollCycleResource extends JsonResource
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
            'periodo' => [
                'inicio' => $this->fecha_inicio->toDateString(),
                'fin' => $this->fecha_fin->toDateString(),
            ],
            'fecha_pago' => $this->fecha_pago?->toDateString(),
            'estado' => $this->estado,
            'is_locked_edition' => $this->isLockedForEdition(),
            'is_locked_calculation' => $this->isLockedForCalculation(),
            'created_at' => $this->created_at,
        ];
    }
}
