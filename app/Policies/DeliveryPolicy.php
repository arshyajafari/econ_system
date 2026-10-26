<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy {
    private function isAdmin(User $user): bool { return $user->hasRole('admin'); }
    private function isAccountant(User $user): bool { return $user->hasRole('accountant'); }
    private function isDeliveryOperator(User $user): bool { return $user->hasRole('delivery operator'); }

    public function viewAny(User $user): bool {
        return $this->isAdmin($user) || $user->can('deliveries.view');
    }

    public function view(User $user, Delivery $delivery): bool { return $this->viewAny($user); }

    public function create(User $user): bool {
        return $this->isAdmin($user) && $user->can('deliveries.create');
    }

    public function update(User $user, Delivery $delivery): bool {
        return $this->isAdmin($user) && $user->can('deliveries.update');
    }

    public function delete(User $user, Delivery $delivery): bool {
        return $this->isAdmin($user) && $user->can('deliveries.delete');
    }

    // Preparation and shipment are approval gates. Only admin may approve them.
    public function prepare(User $user, Delivery $delivery): bool {
        return $this->isAdmin($user) && $user->can('deliveries.prepare');
    }

    public function ship(User $user, Delivery $delivery): bool {
        return $this->isAdmin($user) && $user->can('deliveries.ship');
    }

    // Physical hand-off can still be registered by the delivery operator.
    public function complete(User $user, Delivery $delivery): bool {
        return $this->isDeliveryOperator($user) && $user->can('deliveries.complete');
    }

    public function cancel(User $user, Delivery $delivery): bool {
        return $this->isAdmin($user) && $user->can('deliveries.cancel');
    }

    public function export(User $user): bool {
        return $this->isAdmin($user) || $user->can('deliveries.export');
    }
}
