<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleResource extends JsonResource
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
            'description' => $this->description,
            'system' => (bool) $this->system,
            
            // Computed поля для удобства frontend
            'is_admin' => in_array($this->id, [900, 999]),
            'is_moderator' => in_array($this->id, [500, 900, 999]),
            'is_user' => $this->id === 1,
            'is_developer' => $this->id === 999,
            
            // Уровень доступа (для сортировки/сравнения)
            'level' => $this->id,
        ];
    }
}
