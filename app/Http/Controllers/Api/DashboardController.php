<?php

    namespace App\Http\Controllers;

    use App\Actions\Dashboard\GetDashboardAction;
    use App\Http\Resources\DashboardResource;

    class DashboardController extends Controller {
        public function index(GetDashboardAction $action): DashboardResource {
            return new DashboardResource($action->execute());
        }
    }
