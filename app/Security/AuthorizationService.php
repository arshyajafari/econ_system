<?php

    namespace App\Security;

    use App\Models\User;

    class AuthorizationService {
        public function assignRole(User $user, string ...$roles): void {
            $user->syncRoles($roles);
        }

        public function givePermission(User $user, string ...$permissions): void {
            $user->givePermissionTo($permissions);
        }

        public function revokePermission(User $user, string ...$permissions): void {
            $user->revokePermissionTo($permissions);
        }
    }
