<?php

    namespace App\DTO;

    abstract class BaseData {
        public static function from(array $attributes): static {
            return new static(...$attributes);
        }

        public function toArray(): array {
            return get_object_vars($this);
        }
    }
