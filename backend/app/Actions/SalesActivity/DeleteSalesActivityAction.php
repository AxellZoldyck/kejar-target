<?php

namespace App\Actions\SalesActivity;

use App\Enums\SalesActivityStatus;
use App\Models\SalesActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class DeleteSalesActivityAction
{
    public function execute(User $sales, SalesActivity $activity): void
    {
        $evidencePath = DB::transaction(function () use ($sales, $activity): ?string {
            $locked = SalesActivity::query()
                ->where('company_id', $sales->company_id)
                ->where('sales_id', $sales->id)
                ->lockForUpdate()
                ->findOrFail($activity->id);
            $status = $locked->status instanceof SalesActivityStatus
                ? $locked->status
                : SalesActivityStatus::from($locked->status);

            if ($status !== SalesActivityStatus::DRAFT) {
                throw new ConflictHttpException('Hanya draft yang dapat dihapus.');
            }

            $path = $locked->evidence_path;
            $locked->delete();

            return $path;
        });

        if ($evidencePath) {
            Storage::disk('local')->delete($evidencePath);
        }
    }
}
