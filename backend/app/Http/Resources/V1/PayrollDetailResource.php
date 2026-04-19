<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollDetailResource extends JsonResource
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
            'concept' => $this->concept,
            'label' => $this->label,
            'type' => $this->type,
            'hours' => (float)$this->hours,
            'rate' => (float)$this->rate,
            'amount' => (float)$this->amount,
        ];
    }
}
