<?php

    namespace App\Actions\Doctor;

    use App\Queries\Doctor\DoctorQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListDoctorsAction {
        public function execute(array $filters): LengthAwarePaginator {
            return DoctorQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
