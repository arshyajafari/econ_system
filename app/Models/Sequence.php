<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;

    class Sequence extends BaseModel {
        use HasFactory;

        protected $fillable = [
            'sequence_key',
            'current_value',
            'last_generated_at',
        ];

        protected function casts(): array {
            return [
                'current_value' => 'integer',
                'last_generated_at' => 'immutable_datetime',
            ];
        }
    }
