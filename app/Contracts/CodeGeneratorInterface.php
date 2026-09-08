<?php

    namespace App\Contracts;

    use App\Services\CodeGeneratorData;

    interface CodeGeneratorInterface {
        public function generate(CodeGeneratorData|string $data): string;
    }
