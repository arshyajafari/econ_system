<?php

    namespace App\Actions\Doctor;

    use App\Exceptions\BusinessRuleException;
    use App\Models\Doctor;
    use App\Models\Visit;
    use Illuminate\Support\Facades\DB;

    class DeleteDoctorAction {
        public function execute(Doctor $doctor): void {
            DB::transaction(function () use ($doctor) {
                $doctor = Doctor::query()->lockForUpdate()->findOrFail($doctor->id);

                $hasOperationalHistory = $doctor->addresses()->exists() || Visit::query()
                        ->where('doctor_id', $doctor->id)->exists();

                if ($hasOperationalHistory) {
                    throw new BusinessRuleException('این پزشک دارای سابقه عملیاتی است و امکان حذف آن وجود ندارد.');
                }

                $doctor->delete();
            });
        }
    }
