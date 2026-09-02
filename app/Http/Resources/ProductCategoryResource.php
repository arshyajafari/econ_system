<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class ProductCategoryResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'code' => $this->code,
                'title' => $this->title,
                'parent_id' => $this->parent_id,
                'children' => ProductCategoryResource::collection($this->whenLoaded('childrenRecursive')),
                'sort_order' => $this->sort_order,
                'is_active' => $this->is_active,
                'description' => $this->description,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }
    }
