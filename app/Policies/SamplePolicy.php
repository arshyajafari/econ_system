<?php

    namespace App\Policies;

    use App\Models\Sample;
    use App\Models\User;

    class SamplePolicy {
        public function viewAny(User $user): bool {
            return $user->can('samples.view');
        }

        public function view(User $user, Sample $sample): bool {
            return $user->can('samples.view');
        }

        public function create(User $user): bool {
            return $user->can('samples.create');
        }

        public function update(User $user, Sample $sample): bool {
            return $user->can('samples.update');
        }

        public function delete(User $user, Sample $sample): bool {
            return $user->can('samples.delete');
        }

        public function export(User $user): bool {
            return $user->can('samples.export');
        }
    }
