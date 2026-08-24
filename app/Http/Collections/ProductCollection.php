<?php

    namespace App\Http\Collections;

    use App\Http\Resources\ProductResource;

    class ProductCollection extends BaseCollection {
        public $collects = ProductResource::class;
    }
