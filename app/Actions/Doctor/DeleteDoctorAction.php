<?php

    namespace App\Actions\Doctor;

    use App\Models\Doctor;
    use Illuminate\Support\Facades\DB;

    class DeleteDoctorAction {
        public function execute(Doctor $doctor): void {
            DB::transaction(function () use ($doctor) {
                $doctor->delete();
            });
        }
    }
