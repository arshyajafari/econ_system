<?php

    namespace App\Traits;

    use App\Contracts\CodeGeneratorInterface;

    trait HasCodeGenerator {
        public static function generateCode(): string {
            return app(CodeGeneratorInterface::class)->generate(static::codeGenerator());
        }
    }
