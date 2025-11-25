<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->when(isset($this->phone), $this->phone),
            'email_verified_at' => $this->when(
                isset($this->email_verified_at),
                fn() => $this->email_verified_at?->format('Y-m-d H:i:s')
            ),
            'role_id' => $this->role_id,
            'role' => new UserRoleResource($this->whenLoaded('role')),
            'created_at' => $this->when(
                isset($this->created_at),
                fn() => $this->created_at->format('Y-m-d H:i:s')
            ),
            'updated_at' => $this->when(
                isset($this->updated_at),
                fn() => $this->updated_at->format('Y-m-d H:i:s')
            ),
            
            // Дополнительные computed поля
            'is_admin' => $this->when(
                $this->relationLoaded('role'),
                fn() => in_array($this->role_id, [900, 999])
            ),
            'is_moderator' => $this->when(
                $this->relationLoaded('role'),
                fn() => in_array($this->role_id, [500, 900, 999])
            ),
        ];
    }

    /**
     * Дополнительные мета-данные для ресурса
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}
