<?php

    namespace App\Models;

    use App\Services\CodeGeneratorData;
    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Brand extends BaseModel {
        use HasPublicId, HasFactory, SoftDeletes, HasAudit;

        public const DEFAULT_RELATIONS = [];

        public const SEARCHABLE = [
            'code',
            'title',
        ];

        public const SORTABLE = [
            'title',
            'sort_order',
            'created_at',
        ];

        protected $fillable = [
            'code',
            'title',
            'logo',
            'sort_order',
            'is_active',
            'description',
            'meta',
        ];

        protected function casts(): array {
            return array_merge(parent::casts(), [
                'is_active' => 'boolean',
                'meta' => 'array',
            ]);
        }

        public function products(): HasMany {
            return $this->hasMany(Product::class);
        }

        public function scopeActive($query) {
            return $query->where('is_active', true);
        }

        public function scopeDefaultSort(Builder $query): Builder {
            return $query->orderBy('sort_order')->orderBy('title');
        }

        public function isActive(): bool {
            return $this->is_active;
        }

        public static function codeGenerator(): CodeGeneratorData {
            return new CodeGeneratorData(sequence_key: 'brand', prefix: 'BRA', padding: 6);
        }
    }
