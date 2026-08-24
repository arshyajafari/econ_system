<?php

    namespace App\Queries\Doctor;

    use App\Models\Doctor;
    use App\Queries\BaseQuery;

    class DoctorQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Doctor::query()->with(Doctor::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Doctor::SEARCHABLE);
            $this->applySpecialty($filters['specialty'] ?? null);
            $this->applyStatus($filters['status'] ?? null);
            $this->applySort($filters['sort'] ?? null, Doctor::SORTABLE, 'last_name');

            return $this;
        }

        protected function applySpecialty(?string $specialty): void {
            if (!$specialty) {
                return;
            }

            $this->query->where('specialty', $specialty);
        }

        protected function applyStatus(?string $status): void {
            if (!$status) {
                return;
            }

            $this->query->where('status', $status);
        }
    }
