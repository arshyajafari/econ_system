<?php

    namespace App\Http\Collections;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\ResourceCollection;
    use function App\Http\Resources\method_exists;

    abstract class BaseCollection extends ResourceCollection {
        public function toArray(Request $request): array {
            return [
                'data' => $this->collection,
            ];
        }

        public function with($request): array {
            if (!method_exists($this->resource, 'total')) {
                return [];
            }

            return [

                'pagination' => [
                    'current_page' => $this->currentPage(),
                    'per_page' => $this->perPage(),
                    'total' => $this->total(),
                    'last_page' => $this->lastPage(),
                    'from' => $this->firstItem(),
                    'to' => $this->lastItem(),
                ],

            ];
        }
    }
