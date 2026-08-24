<?php

    namespace App\Actions\DoctorVisit;

    use App\Models\Doctor;
    use App\Models\DoctorVisit;
    use Illuminate\Support\Facades\DB;

    class UpdateDoctorVisitAction {
        public function execute(DoctorVisit $doctorVisit, array $data): DoctorVisit {
            return DB::transaction(function () use (
                $doctorVisit, $data
            ) {
                $doctorVisit = DoctorVisit::query()->lockForUpdate()->findOrFail($doctorVisit->id);

                $doctor = Doctor::query()->where('public_id', $data['doctor_id'])->firstOrFail();

                $doctorVisit->update([
                    'doctor_id' => $doctor->id,
                    'visit_date' => $data['visit_date'],
                    'description' => $data['description'] ?? null,
                ]);

                $doctorVisit->samples()->delete();

                foreach ($data['samples'] ?? [] as $sampleData) {
                    $productId = \App\Models\Product::query()->where('public_id', $sampleData['product_id'])
                        ->value('id');

                    $doctorVisit->samples()->create([
                        'product_id' => $productId,
                        'quantity' => (int)$sampleData['quantity'],
                        'description' => $sampleData['description'] ?? null,
                    ]);
                }

                return $doctorVisit->fresh(DoctorVisit::DEFAULT_RELATIONS);
            });
        }
    }
