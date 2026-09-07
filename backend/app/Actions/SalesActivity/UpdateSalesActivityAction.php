<?php

namespace App\Actions\SalesActivity;

use App\Enums\SalesActivityStatus;
use App\Models\SalesActivity;
use App\Models\User;
use App\Services\InputWindowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UpdateSalesActivityAction
{
    public function __construct(private readonly InputWindowService $inputWindow) {}

    public function execute(User $sales, SalesActivity $activity, array $data): SalesActivity
    {
        [$updated, $oldEvidence] = DB::transaction(function () use ($sales, $activity, $data): array {
            $locked = SalesActivity::query()
                ->where('company_id', $sales->company_id)
                ->where('sales_id', $sales->id)
                ->lockForUpdate()
                ->findOrFail($activity->id);
            $status = $locked->status instanceof SalesActivityStatus
                ? $locked->status
                : SalesActivityStatus::from($locked->status);

            if (! in_array($status, [SalesActivityStatus::DRAFT, SalesActivityStatus::REJECTED], true)) {
                throw new ConflictHttpException('Hanya draft atau aktivitas ditolak yang dapat diubah.');
            }

            $locked->loadMissing('company');
            $this->inputWindow->assertAllowed(
                $locked->company,
                $data['activity_date'] ?? $locked->activity_date,
            );

            $oldEvidence = $locked->evidence_path;
            $locked->update($data);

            return [$locked->refresh(), $oldEvidence];
        });

        if (isset($data['evidence_path']) && $oldEvidence && $oldEvidence !== $updated->evidence_path) {
            Storage::disk('local')->delete($oldEvidence);
        }

        return $updated;
    }
}
