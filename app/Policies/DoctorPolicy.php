<?php

    namespace App\Policies;

    use App\Models\Doctor;
    use App\Models\User;

    class DoctorPolicy {
        public function viewAny(User $user): bool {
            return $user->can('doctors.view');
        }

        public function view(User $user, Doctor $doctor): bool {
            return $user->can('doctors.view');
        }

        public function create(User $user): bool {
            return $user->can('doctors.create');
        }

        public function update(User $user, Doctor $doctor): bool {
            return $user->can('doctors.update');
        }

        public function delete(User $user, Doctor $doctor): bool {
            return $user->can('doctors.delete');
        }

        public function restore(User $user, Doctor $doctor): bool {
            return $user->can('doctors.restore');
        }

        public function changeStatus(User $user, Doctor $doctor): bool {
            return $user->can('doctors.change_status');
        }

        public function export(User $user): bool {
            return $user->can('doctors.export');
        }
    }
