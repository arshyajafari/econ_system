<?php

    namespace App\Queries\Doctor;

    use App\Models\Visit;
    use App\Queries\BaseQuery;

    class VisitQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Visit::query()->with(Visit::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Visit::SEARCHABLE);
            $this->applyDoctor($filters['doctor_id'] ?? null);
            $this->applyEmployee($filters['employee_id'] ?? null);
            $this->applyStatus($filters['status'] ?? null);
            $this->applyDateRange($filters['visit_from'] ?? null, $filters['visit_to'] ?? null);
            $this->applySort($filters['sort'] ?? null, Visit::SORTABLE, 'visit_date');

            return $this;
        }

        protected function applyDoctor(?string $doctorId): void {
            if (!$doctorId) {
                return;
            }

            $this->query->whereHas('doctor', function ($query) use ($doctorId) {
                $query->where('public_id', $doctorId);
            });
        }

        protected function applyEmployee(?string $employeeId): void {
            if (!$employeeId) {
                return;
            }

            $this->query->whereHas('employee', function ($query) use ($employeeId) {
                $query->where('public_id', $employeeId);
            });
        }

        protected function applyStatus(?string $status): void {
            if (!$status) {
                return;
            }

            $this->query->where('status', $status);
        }

        protected function applyDateRange(?string $from, ?string $to): void {
            if ($from) {
                $this->query->whereDate('visit_date', '>=', $from);
            }

            if ($to) {
                $this->query->whereDate('visit_date', '<=', $to);
            }
        }
    }
