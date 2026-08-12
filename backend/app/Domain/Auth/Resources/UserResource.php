<?php

declare(strict_types=1);

namespace App\Domain\Auth\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->name,
            'email' => $this->email,
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
