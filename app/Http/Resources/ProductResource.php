<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class ProductResource extends JsonResource {
        /**
         * @param Request $request
         */
        public function toArray($request): array {
            return [
                'id' => $this->public_id,
                'code' => $this->code,
                'title' => $this->title,
                'image' => $this->image,
                'barcode' => $this->barcode,
                'status' => $this->status,
                'sort_order' => $this->sort_order,
                'description' => $this->description,
                'brand' => $this->whenLoaded('brand', fn() => [
                    'id' => $this->brand->public_id,
                    'title' => $this->brand->title,
                ]),
                'category' => $this->whenLoaded('category', fn() => [
                    'id' => $this->category->public_id,
                    'title' => $this->category->title,
                ]),
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }
    }
