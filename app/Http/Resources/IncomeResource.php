<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'account' => new AccountResource($this->whenLoaded('account')),
            'amount' => number_format($this->amount, 2),
            'date' => $this->date->format('d-m-Y'),
        ];
    }
}
