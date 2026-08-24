<?php

    namespace App\Actions\DoctorVisit;

    use App\Models\DoctorVisit;
    use Illuminate\Support\Facades\DB;

    class DeleteDoctorVisitAction {
        public function execute(DoctorVisit $doctorVisit): void {
            DB::transaction(function () use ($doctorVisit) {
                $doctorVisit = DoctorVisit::query()->lockForUpdate()->findOrFail($doctorVisit->id);

                $doctorVisit->delete();
            });
        }
    }
