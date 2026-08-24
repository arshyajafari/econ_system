<?php

    namespace App\Contracts;

    use App\Services\CodeGeneratorData;

    interface HasGeneratedCode {
        public static function codeGenerator(): CodeGeneratorData;
    }
