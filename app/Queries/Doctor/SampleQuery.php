<?php

    namespace App\Queries\Doctor;

    use App\Models\Sample;
    use App\Queries\BaseQuery;

    class SampleQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Sample::query()->with(Sample::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Sample::SEARCHABLE);
            $this->applyVisit($filters['visit_id'] ?? null);
            $this->applyProduct($filters['product_id'] ?? null);
            $this->applySort($filters['sort'] ?? null, Sample::SORTABLE, 'created_at');

            return $this;
        }

        protected function applyVisit(?string $visitId): void {
            if (!$visitId) {
                return;
            }

            $this->query->whereHas('visit', function ($query) use ($visitId) {
                $query->where('public_id', $visitId);
            });
        }

        protected function applyProduct(?string $productId): void {
            if (!$productId) {
                return;
            }

            $this->query->whereHas('product', function ($query) use ($productId) {
                $query->where('public_id', $productId);
            });
        }
    }
