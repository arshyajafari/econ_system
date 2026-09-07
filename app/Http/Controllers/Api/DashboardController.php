<?php

namespace App\Http\Controllers\Api;

use App\Actions\Dashboard\GetDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, GetDashboardAction $action): DashboardResource
    {
        $this->authorize('viewDashboard', $request->user());

        return new DashboardResource($action->execute());
    }
}
