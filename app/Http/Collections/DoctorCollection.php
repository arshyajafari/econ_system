<?php

    namespace App\Http\Resources\Doctor;

    use App\Http\Resources\DoctorResource;
    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\ResourceCollection;

    class DoctorCollection extends ResourceCollection {
        public $collects = DoctorResource::class;

        public function toArray(Request $request): array {
            return parent::toArray($request);
        }
    }
