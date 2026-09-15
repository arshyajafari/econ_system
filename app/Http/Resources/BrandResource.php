<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'code' => $this->code,
            'title' => $this->title,
            'logo' => $this->logo && !filter_var($this->logo, FILTER_VALIDATE_URL)
                ? Storage::disk('public')->url($this->logo)
                : $this->logo,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
