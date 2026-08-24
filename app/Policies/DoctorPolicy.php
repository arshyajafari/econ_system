<?php

    namespace App\Policies;

    use App\Models\Doctor;
    use App\Models\User;

    class DoctorPolicy {
        public function viewAny(User $user): bool {
            return $user->hasPermission('doctors.view');
        }

        public function view(User $user, Doctor $doctor): bool {
            return $user->hasPermission('doctors.view');
        }

        public function create(User $user): bool {
            return $user->hasPermission('doctors.create');
        }

        public function update(User $user, Doctor $doctor): bool {
            return $user->hasPermission('doctors.update');
        }

        public function delete(User $user, Doctor $doctor): bool {
            return $user->hasPermission('doctors.delete');
        }

        public function restore(User $user, Doctor $doctor): bool {
            return $user->hasPermission('doctors.restore');
        }

        public function changeStatus(User $user, Doctor $doctor): bool {
            return $user->hasPermission('doctors.change_status');
        }

        public function export(User $user): bool {
            return $user->hasPermission('doctors.export');
        }
    }
