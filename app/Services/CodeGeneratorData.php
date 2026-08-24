<?php

    namespace App\Services;

    use App\Enums\SequenceResetType;

    final readonly class CodeGeneratorData {
        public function __construct(public string $sequence_key, public string $prefix, public int $padding = 6,
            public string $separator = '-', public SequenceResetType $reset = SequenceResetType::NONE) {
        }
    }
