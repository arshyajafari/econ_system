<?php

    namespace App\Models;

    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class DoctorVisitSample extends BaseModel {
        use HasPublicId, HasAudit, SoftDeletes;

        public const array DEFAULT_RELATIONS = [
            'doctorVisit',
            'product',
        ];

        protected $fillable = [
            'doctor_visit_id',
            'product_id',
            'quantity',
            'description',
        ];

        protected $casts = [
            'quantity' => 'integer',
        ];

        public function doctorVisit(): BelongsTo {
            return $this->belongsTo(DoctorVisit::class);
        }

        public function product(): BelongsTo {
            return $this->belongsTo(Product::class);
        }
    }
