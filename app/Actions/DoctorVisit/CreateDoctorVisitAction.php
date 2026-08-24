<?php

    namespace App\Actions\DoctorVisit;

    use App\Models\Doctor;
    use App\Models\DoctorVisit;
    use App\Models\Product;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class CreateDoctorVisitAction {
        public function execute(array $data, User $user): DoctorVisit {
            return DB::transaction(function () use ($data, $user) {
                $employee = $user->employee;

                if (!$employee) {
                    throw new RuntimeException('کاربر فعلی به کارمند متصل نیست.');
                }

                $doctor = Doctor::query()->where('public_id', $data['doctor_id'])->firstOrFail();

                $visit = DoctorVisit::create([
                    'doctor_id' => $doctor->id,
                    'employee_id' => $employee->id,
                    'visit_date' => $data['visit_date'],
                    'description' => $data['description'] ?? null,
                ]);

                foreach ($data['samples'] ?? [] as $sampleData) {
                    $product = Product::query()->where('public_id', $sampleData['product_id'])->firstOrFail();

                    $visit->samples()->create([
                        'product_id' => $product->id,
                        'quantity' => (int)$sampleData['quantity'],
                        'description' => $sampleData['description'] ?? null,
                    ]);
                }

                return $visit->fresh(DoctorVisit::DEFAULT_RELATIONS);
            });
        }
    }
