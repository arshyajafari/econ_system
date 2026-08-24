<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    abstract class BaseResource extends JsonResource {
        public function with(Request $request): array {
            return [
                'success' => true,
            ];
        }
    }
