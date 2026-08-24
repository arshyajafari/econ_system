<?php

    namespace App\Http\Requests;

    use App\Http\Requests\BaseFormRequest;

    abstract class IndexRequest extends BaseFormRequest {
        public function perPage(int $default = 20): int {
            return (int)$this->input('per_page', $default);
        }

        public function page(int $default = 1): int {
            return (int)$this->input('page', $default);
        }

        public function search(): ?string {
            return $this->input('search');
        }

        public function sort(): ?string {
            return $this->input('sort');
        }

        protected function commonRules(): array {
            return [
                'search' => [
                    'nullable',
                    'string',
                    'max:200',
                ],
                'sort' => [
                    'nullable',
                    'string',
                ],
                'page' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],
                'per_page' => [
                    'nullable',
                    'integer',
                    'min:10',
                    'max:100',
                ],
            ];
        }
    }
