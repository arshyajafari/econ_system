<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    abstract class BaseModel extends Model {
        protected static $modelsShouldPreventLazyLoading = true;

        public const DEFAULT_RELATIONS = [];

        protected function casts(): array {
            return [
                'created_at' => 'immutable_datetime',
                'updated_at' => 'immutable_datetime',
                'deleted_at' => 'immutable_datetime',
            ];
        }
    }
