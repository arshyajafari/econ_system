<?php

    namespace App\Actions\Sample;

    use App\Queries\Sample\SampleQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListSamplesAction {
        public function execute(array $filters): LengthAwarePaginator {
            return SampleQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
