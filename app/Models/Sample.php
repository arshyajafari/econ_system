<?php

    namespace App\Models;

    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Sample extends BaseModel {
        use HasPublicId, HasAudit, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'visit.doctor',
            'visit.employee',
            'product',
        ];

        public const SEARCHABLE = [
            'description',
        ];

        public const SORTABLE = [
            'quantity',
            'created_at',
        ];

        protected $fillable = [
            'visit_id',
            'product_id',
            'quantity',
            'description',
            'meta',
        ];

        protected $casts = [
            'quantity' => 'integer',
            'meta' => 'array',
        ];

        public function visit(): BelongsTo {
            return $this->belongsTo(Visit::class);
        }

        public function product(): BelongsTo {
            return $this->belongsTo(Product::class);
        }
    }
