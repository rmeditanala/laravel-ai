<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'response' => $this->resource['response'],
            'model' => $this->resource['model'] ?? null,
            'usage' => $this->resource['usage'] ?? [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
        ];
    }
}
