<?php

    namespace App\Models;

    use App\Traits\HasAudit;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\SoftDeletes;
    use Illuminate\Foundation\Auth\User as Authenticatable;

    abstract class BaseAuthenticatable extends Authenticatable {
        use HasFactory;
        use SoftDeletes;
        use HasAudit;

        protected $fillable = [];

        protected function casts(): array {
            return [
                'created_at' => 'immutable_datetime',
                'updated_at' => 'immutable_datetime',
                'deleted_at' => 'immutable_datetime',
            ];
        }
    }
