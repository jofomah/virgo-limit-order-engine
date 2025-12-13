<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,

            'email_verified' => (bool) $this->email_verified_at,

            // Wallet balances (Money value objects)
            'balance' => [
                'currency'  => $this->currency,
                'available' => $this->balance_available_units,  // MoneyCast returns Money object
                'locked'    => $this->balance_locked_units,
            ],

            // Asset balances (if assets relation exists)
            'assets' => $this->whenLoaded('assets'),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
