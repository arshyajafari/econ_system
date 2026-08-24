<?php

    namespace App\Traits;

    use Illuminate\Support\Str;

    trait HasPublicId {
        protected static function bootHasPublicId(): void {
            static::creating(function ($model) {
                if (!$model->getAttribute('public_id')) {
                    $model->public_id = (string)Str::ulid();
                }
            });
        }

        public function getRouteKeyName(): string {
            return 'public_id';
        }
    }
