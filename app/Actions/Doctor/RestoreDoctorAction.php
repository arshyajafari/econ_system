<?php

    namespace App\Actions\Doctor;

    use App\Models\Doctor;
    use Illuminate\Support\Facades\DB;

    class RestoreDoctorAction {
        public function execute(Doctor $doctor): Doctor {
            return DB::transaction(function () use ($doctor) {
                $doctor->restore();

                return $doctor->fresh(Doctor::DEFAULT_RELATIONS);
            });
        }
    }
