<?php

namespace App\Http\Controllers\Api;

use App\Actions\Report\GetReportAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ReportRequest;
use App\Http\Resources\ReportResource;

class ReportController extends Controller
{
    public function index(ReportRequest $request, GetReportAction $action): ReportResource
    {
        return new ReportResource($action->execute($request->string('from')->toString(), $request->string('to')->toString()));
    }
}
