<?php

    namespace App\Actions;

    use Illuminate\Support\Facades\DB;

    abstract class BaseAction {
        protected function transaction(callable $callback): mixed {
            return DB::transaction($callback);
        }

        protected function run(callable $callback): mixed {
            return $callback();
        }

        protected function afterCommit(callable $callback): void {
            DB::afterCommit($callback);
        }
    }
