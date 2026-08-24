<?php

    namespace App\Queries;

    use Illuminate\Contracts\Pagination\LengthAwarePaginator;
    use Illuminate\Database\Eloquent\Builder;

    abstract class BaseQuery {
        protected Builder $query;

        public function __construct() {
            $this->initialize();
        }

        abstract protected function initialize(): void;

        public static function make(): static {
            return new static();
        }

        protected function getQuery(): Builder {
            return $this->query;
        }

        protected function applySearch(?string $search, array $fields): void {
            if (!$search || empty($fields)) {
                return;
            }

            $this->query->where(function (Builder $query) use (
                $search, $fields
            ) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        protected function applySort(?string $sort, array $allowedSorts, string $default = 'created_at'): void {
            $direction = 'asc';

            if (!$sort) {
                $sort = $default;

            } elseif (str_starts_with($sort, '-')) {
                $direction = 'desc';

                $sort = substr($sort, 1);
            }

            if (!in_array($sort, $allowedSorts, true)) {
                $sort = $default;
            }

            $this->query->orderBy($sort, $direction);
        }

        public function paginate(int $perPage = 20): LengthAwarePaginator {
            return $this->query->paginate($perPage)->withQueryString();
        }
    }
