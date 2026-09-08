<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Dashboard\GetDashboardAction;
    use App\Http\Controllers\Controller;
    use App\Http\Resources\DashboardResource;
    use Illuminate\Http\Request;

    class DashboardController extends Controller {
        public function index(Request $request, GetDashboardAction $action): DashboardResource {
            abort_unless($request->user()->can('dashboard.view'), 403);

            return new DashboardResource($action->execute());
        }
    }
