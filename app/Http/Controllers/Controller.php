<?php

    namespace App\Http\Controllers;

    use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
    use Illuminate\Foundation\Validation\ValidatesRequests;
    use Illuminate\Routing\Controller as LaravelController;

    abstract class Controller extends LaravelController {
        use AuthorizesRequests;
        use ValidatesRequests;

        protected function authorizeModel(string $model, string $parameter): void {
            $this->authorizeResource($model, $parameter);
        }
    }
