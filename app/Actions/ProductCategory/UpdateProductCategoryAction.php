<?php

    namespace App\Actions\ProductCategory;

    use App\Actions\BaseAction;
    use App\Models\ProductCategory;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Validation\ValidationException;

    class UpdateProductCategoryAction extends BaseAction {
        public function execute(ProductCategory $category, array $data): ProductCategory {
            return DB::transaction(function () use ($category, $data) {
                if (array_key_exists('parent_id', $data)) {
                    if ($data['parent_id'] === null || $data['parent_id'] === '') {
                        $data['parent_id'] = null;
                    } else {
                        $parent = ProductCategory::query()
                            ->where('public_id', $data['parent_id'])
                            ->firstOrFail();

                        if ($parent->is($category)) {
                            throw ValidationException::withMessages([
                                'parent_id' => 'یک دسته‌بندی نمی‌تواند والد خودش باشد.',
                            ]);
                        }

                        $ancestor = $parent;
                        while ($ancestor->parent_id !== null) {
                            if ((int) $ancestor->parent_id === (int) $category->id) {
                                throw ValidationException::withMessages([
                                    'parent_id' => 'نمی‌توان یک زیرمجموعه را به‌عنوان والد این دسته‌بندی انتخاب کرد.',
                                ]);
                            }

                            $ancestor = $ancestor->parent;
                            if (!$ancestor) {
                                break;
                            }
                        }

                        $data['parent_id'] = $parent->id;
                    }
                }

                $category->fill($data);
                $category->save();

                return $category->fresh(ProductCategory::DEFAULT_RELATIONS);
            });
        }
    }
