<?php

    namespace App\Policies;

    use App\Models\ProductCategory;
    use App\Models\User;

    class ProductCategoryPolicy {
        public function viewAny(User $user): bool {
            return $user->can('product_categories.view');
        }

        public function view(User $user, ProductCategory $category): bool {
            return $user->can('product_categories.view');
        }

        public function create(User $user): bool {
            return $user->can('product_categories.create');
        }

        public function update(User $user, ProductCategory $category): bool {
            return $user->can('product_categories.update');
        }

        public function delete(User $user, ProductCategory $category): bool {
            return $user->can('product_categories.delete');
        }

        public function changeActivity(User $user, ProductCategory $category): bool {
            return $user->can('product_categories.change_activity');

        }
    }
