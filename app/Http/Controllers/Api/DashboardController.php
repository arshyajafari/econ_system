<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Dashboard\GetDashboardAction;
    use App\Http\Controllers\Controller;
    use App\Http\Resources\DashboardResource;

    class DashboardController extends Controller {
        public function index(GetDashboardAction $action): DashboardResource {
            return new DashboardResource($action->execute());
        }
    }
