<?php

    namespace App\Queries\Doctor;

    use App\Models\DoctorVisit;
    use App\Queries\BaseQuery;

    class DoctorVisitQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = DoctorVisit::query()->with(DoctorVisit::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, DoctorVisit::SEARCHABLE);
            $this->applyDoctor($filters['doctor_id'] ?? null);
            $this->applyEmployee($filters['employee_id'] ?? null);
            $this->applyDateRange($filters['visit_from'] ?? null, $filters['visit_to'] ?? null);
            $this->applySort($filters['sort'] ?? null, DoctorVisit::SORTABLE, 'visit_date');

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

        protected function applyDateRange(?string $from, ?string $to): void {
            if ($from) {
                $this->query->whereDate('visit_date', '>=', $from);
            }

            if ($to) {
                $this->query->whereDate('visit_date', '<=', $to);
            }
        }
    }
