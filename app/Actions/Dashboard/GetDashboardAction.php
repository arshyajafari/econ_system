<?php

    namespace App\Actions\Dashboard;

    use App\Services\DashboardService;

    class GetDashboardAction {
        public function __construct(protected DashboardService $dashboardService) {
        }

        public function execute(): array {
            return $this->dashboardService->summary();
        }
    }
