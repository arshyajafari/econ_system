<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class DoctorVisitSampleResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'product' => $this->whenLoaded('product', fn() => [
                    'id' => $this->product->public_id,
                    'code' => $this->product->code,
                    'title' => $this->product->title,
                ]),
                'quantity' => $this->quantity,
                'description' => $this->description,
            ];
        }
    }
