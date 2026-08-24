<?php

    namespace App\Http\Resources\Brand;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class BrandResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'code' => $this->code,
                'title' => $this->title,
                'logo' => $this->logo,
                'sort_order' => $this->sort_order,
                'is_active' => $this->is_active,
                'description' => $this->description,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }
    }
