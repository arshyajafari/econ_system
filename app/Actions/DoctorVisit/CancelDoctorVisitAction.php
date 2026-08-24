<?php

    namespace App\Actions\DoctorVisit;

    use App\Enums\DoctorVisitStatus;
    use App\Models\DoctorVisit;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class CancelDoctorVisitAction {
        public function execute(DoctorVisit $doctorVisit): DoctorVisit {
            return DB::transaction(function () use ($doctorVisit) {
                $doctorVisit = DoctorVisit::query()->lockForUpdate()->findOrFail($doctorVisit->id);

                if ($doctorVisit->status !== DoctorVisitStatus::PLANNED) {
                    throw new RuntimeException('فقط ویزیت در وضعیت planned قابل لغو است.');
                }

                $doctorVisit->status = DoctorVisitStatus::CANCELLED;
                $doctorVisit->save();

                return $doctorVisit->fresh(DoctorVisit::DEFAULT_RELATIONS);
            });
        }
    }
