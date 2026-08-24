<?php

    namespace App\Queries\Employee;

    use App\Models\Employee;
    use App\Queries\BaseQuery;

    class EmployeeQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Employee::query()->with(Employee::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Employee::SEARCHABLE);
            $this->applyStatus($filters['status'] ?? null);
            $this->applyEmploymentType($filters['employment_type'] ?? null);
            $this->applyGender($filters['gender'] ?? null);
            $this->applySort($filters['sort'] ?? null, Employee::SORTABLE, 'last_name');

            return $this;
        }

        protected function applyStatus(?string $status): void {
            if (!$status) {
                return;
            }

            $this->query->where('status', $status);
        }

        protected function applyEmploymentType(?string $employmentType): void {
            if (!$employmentType) {
                return;
            }

            $this->query->where('employment_type', $employmentType);
        }

        protected function applyGender(?string $gender): void {
            if (!$gender) {
                return;
            }

            $this->query->where('gender', $gender);
        }
    }
