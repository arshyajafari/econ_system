<?php

    namespace App\Models;

    use App\Services\CodeGeneratorData;
    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class ProductCategory extends BaseModel {
        use HasPublicId, HasFactory, SoftDeletes, HasAudit;

        public const DEFAULT_RELATIONS = [
            'parent'
        ];

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
            'parent_id',
            'title',
            'description',
            'sort_order',
            'is_active',
            'meta',
        ];

        protected function casts(): array {
            return array_merge(parent::casts(), [
                'is_active' => 'boolean',
                'meta' => 'array',
            ]);
        }

        public function parent(): BelongsTo {
            return $this->belongsTo(self::class, 'parent_id');
        }

        public function children(): HasMany {
            return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('title');
        }

        public function products(): HasMany {
            return $this->hasMany(Product::class);
        }

        public function childrenRecursive() {
            return $this->children()->with('childrenRecursive');
        }

        public function scopeActive($query) {
            return $query->where('is_active', true);
        }

        public function scopeDefaultSort(Builder $query): Builder {
            return $query->orderBy('sort_order')->orderBy('title');
        }

        public static function codeGenerator(): CodeGeneratorData {
            return new CodeGeneratorData(sequence_key: 'product_category', prefix: 'ProCat', padding: 6);
        }
    }
