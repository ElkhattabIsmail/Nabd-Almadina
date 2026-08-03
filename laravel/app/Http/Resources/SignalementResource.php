<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignalementResource extends JsonResource
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
            'description' => $this->description,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'photo' => $this->photo ? asset('storage/' . $this->photo) : null,
            'category' => $this->category,
            'priority' => $this->priority,
            'urgency' => (int) $this->urgency,
            'summary' => $this->summary,
            'status' => $this->status,
            'user' => new UserResource($this->whenLoaded('user')),
            'departement' => new DepartementResource($this->whenLoaded('departement')),
            'incident' => new IncidentResource($this->whenLoaded('incident')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
