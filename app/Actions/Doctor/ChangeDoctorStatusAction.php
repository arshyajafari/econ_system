<?php

    namespace App\Actions\Doctor;

    use App\Enums\DoctorStatus;
    use App\Models\Doctor;
    use Illuminate\Support\Facades\DB;

    class ChangeDoctorStatusAction {
        public function execute(Doctor $doctor, DoctorStatus $status): Doctor {
            DB::transaction(function () use ($doctor, $status) {
                if ($doctor->status !== $status) {

                    $doctor->status = $status;

                    $doctor->save();
                }
            });

            return $doctor->fresh(Doctor::DEFAULT_RELATIONS);
        }
    }
