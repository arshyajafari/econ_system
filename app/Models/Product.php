<?php

    namespace App\Models;

    use App\Contracts\HasGeneratedCode;
    use App\Enums\ProductStatus;
    use App\Services\CodeGeneratorData;
    use App\Traits\HasAudit;
    use App\Traits\HasCodeGenerator;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Product extends BaseModel implements HasGeneratedCode {
        use HasPublicId, HasFactory, HasCodeGenerator, SoftDeletes, HasAudit;

        public const DEFAULT_PER_PAGE = 20;
        public const MAX_PER_PAGE = 500;

        public const DEFAULT_RELATIONS = [
            'brand',
            'category',
        ];

        public const SEARCHABLE = [
            'code',
            'title',
            'barcode',
        ];

        public const SORTABLE = [
            'title',
            'sort_order',
            'created_at',
        ];

        protected $fillable = [
            'code',
            'brand_id',
            'product_category_id',
            'title',
            'image',
            'barcode',
            'sort_order',
            'status',
            'description',
            'meta',
        ];

        protected function casts(): array {
            return array_merge(parent::casts(), [
                'status' => ProductStatus::class,
                'meta' => 'array',
            ]);
        }

        public function brand(): BelongsTo {
            return $this->belongsTo(Brand::class);
        }

        public function category(): BelongsTo {
            return $this->belongsTo(ProductCategory::class, 'product_category_id');
        }

        public function orderItems(): HasMany {
            return $this->hasMany(OrderItem::class);
        }

        public function inventoryBatches(): HasMany {
            return $this->hasMany(InventoryBatch::class);
        }

        public function scopeActive(Builder $query): Builder {
            return $query->where('status', ProductStatus::ACTIVE->value);
        }

        public function scopeInactive(Builder $query): Builder {
            return $query->where('status', ProductStatus::INACTIVE->value);
        }

        public function scopeSearch(Builder $query, string $value): Builder {
            return $query->where(function (Builder $query) use ($value) {
                $query->where('title', 'like', "%{$value}%")->orWhere('barcode', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%");
            });
        }

        public function scopeFilter(Builder $query, array $filters): Builder {
            return $query->when($filters['search'] ?? null, fn(Builder $q, $value) => $q->search($value))
                ->when($filters['brand_id'] ?? null, fn(Builder $q, $value) => $q->where('brand_id', $value))
                ->when($filters['product_category_id'] ?? null,
                    fn(Builder $q, $value) => $q->where('product_category_id', $value))->when(array_key_exists('status',
                    $filters), fn(Builder $q) => $q->where('status', $filters['status']));
        }

        public function scopeDefaultSort(Builder $query): Builder {
            return $query->orderBy('sort_order')->orderBy('title');
        }

        public function canBeDeleted(): bool {
            if ($this->orderItems()->exists()) {
                return false;
            }

            if ($this->inventoryBatches()->whereHas('movements')->exists()) {
                return false;
            }

            return true;
        }

        public static function codeGenerator(): CodeGeneratorData {
            return new CodeGeneratorData(sequence_key: 'product', prefix: 'PRO', padding: 6);
        }
    }
