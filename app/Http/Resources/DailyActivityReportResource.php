<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyActivityReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee' => [
                'id' => $this->employee?->public_id,
                'name' => $this->employee_name_snapshot,
            ],
            'visit_date' => $this->visit_date?->toDateString(),
            'visit_time' => $this->visit_time,
            'organization_name' => $this->organization_name,
            'contact_name' => $this->contact_name,
            'contact_type' => $this->contact_type,
            'purpose' => $this->purpose,
            'discussion' => $this->discussion,
            'employee_question' => $this->employee_question,
            'audience_feedback' => $this->audience_feedback,
            'outcome' => $this->outcome,
            'result' => $this->result,
            'is_archived' => $this->retention_until?->toDateString() <= now('Asia/Tehran')->toDateString(),
            'retention_until' => $this->retention_until?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'revisions' => $this->whenLoaded('revisions', fn () => $this->revisions->map(fn ($revision) => [
                'id' => $revision->id,
                'reason' => $revision->reason,
                'old_values' => $revision->old_values,
                'new_values' => $revision->new_values,
                'created_at' => $revision->created_at?->toISOString(),
            ])),
        ];
    }
}
