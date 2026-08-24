<?php

    namespace App\Actions\Doctor;

    use App\Models\Doctor;

    class ShowDoctorAction {
        public function execute(Doctor $doctor): Doctor {
            return $doctor->loadMissing(Doctor::DEFAULT_RELATIONS);
        }
    }
