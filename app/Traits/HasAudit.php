<?php

    namespace App\Traits;

    use App\Models\User;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    trait HasAudit {
        public function creator(): BelongsTo {
            return $this->belongsTo(User::class, 'created_by');
        }

        public function updater(): BelongsTo {
            return $this->belongsTo(User::class, 'updated_by');
        }

        public function deleter(): BelongsTo {
            return $this->belongsTo(User::class, 'deleted_by');
        }
    }
