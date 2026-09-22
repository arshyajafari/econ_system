<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;

class VisitPolicy {
    private function isScientificVisitor(User $user): bool { return $user->hasRole('scientific visitor'); }

    public function viewAny(User $user): bool { return $this->isScientificVisitor($user) || $user->can('visits.view'); }
    public function view(User $user, Visit $visit): bool { return $this->isScientificVisitor($user) || $user->can('visits.view'); }
    public function create(User $user): bool { return $this->isScientificVisitor($user) || $user->can('visits.create'); }
    public function update(User $user, Visit $visit): bool { return $this->isScientificVisitor($user) || $user->can('visits.update'); }
    public function delete(User $user, Visit $visit): bool { return $user->hasRole('admin') && $user->can('visits.delete'); }
    public function complete(User $user, Visit $visit): bool { return $this->isScientificVisitor($user) || $user->can('visits.complete'); }
    public function cancel(User $user, Visit $visit): bool { return $this->isScientificVisitor($user) || $user->can('visits.cancel'); }
    public function export(User $user): bool { return $user->can('visits.export'); }
}