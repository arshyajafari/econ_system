<?php

    namespace App\Policies;

    use App\Models\DoctorVisit;
    use App\Models\User;

    class DoctorVisitPolicy {
        public function viewAny(User $user): bool {
            return $user->can('doctor_visits.view');
        }

        public function view(User $user, DoctorVisit $doctorVisit): bool {
            return $user->can('doctor_visits.view');
        }

        public function create(User $user): bool {
            return $user->can('doctor_visits.create');
        }

        public function update(User $user, DoctorVisit $doctorVisit): bool {
            return $user->can('doctor_visits.update');
        }

        public function delete(User $user, DoctorVisit $doctorVisit): bool {
            return $user->can('doctor_visits.delete');
        }

        public function export(User $user): bool {
            return $user->can('doctor_visits.export');
        }
    }
