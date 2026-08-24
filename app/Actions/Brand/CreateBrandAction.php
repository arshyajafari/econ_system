<?php

    namespace App\Actions\Brand;

    use App\Models\Brand;
    use App\Services\CodeGeneratorService;
    use Illuminate\Support\Facades\DB;

    class CreateBrandAction {
        public function __construct(protected CodeGeneratorService $codeGenerator) {
        }

        public function execute(array $data): Brand {
            return DB::transaction(function () use ($data) {
                $data['code'] = $this->codeGenerator->generate(Brand::class);
                $brand = new Brand();
                $brand->fill($data);
                $brand->save();

                return $brand->fresh(Brand::DEFAULT_RELATIONS);
            });
        }
    }
