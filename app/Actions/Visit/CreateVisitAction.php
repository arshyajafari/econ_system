<?php

    namespace App\Actions\Visit;

    use App\Enums\VisitStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Doctor;
    use App\Models\User;
    use App\Models\Visit;
    use Illuminate\Support\Facades\DB;

    class CreateVisitAction {
        public function execute(array $data, User $user): Visit {
            return DB::transaction(function () use ($data, $user) {
                $employee = $user->employee;

                if (!$employee) {
                    throw new BusinessRuleException('کاربر فعلی به کارمند متصل نیست.');
                }

                if (!empty($data['client_operation_id'])) {
                    $existingVisit = Visit::query()->where('client_operation_id', $data['client_operation_id'])
                        ->first();

                    if ($existingVisit) {
                        return $existingVisit->fresh(Visit::DEFAULT_RELATIONS);
                    }
                }

                $doctor = Doctor::query()->where('public_id', $data['doctor_id'])->firstOrFail();

                $visit = Visit::create([
                    'client_operation_id' => $data['client_operation_id'] ?? null,
                    'doctor_id' => $doctor->id,
                    'employee_id' => $employee->id,
                    'status' => VisitStatus::DRAFT,
                    'visit_date' => $data['visit_date'],
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'location_accuracy' => $data['location_accuracy'] ?? null,
                    'location_captured_at' => $data['location_captured_at'] ?? null,
                    'purpose' => $data['purpose'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                return $visit->fresh(Visit::DEFAULT_RELATIONS);
            });
        }
    }
