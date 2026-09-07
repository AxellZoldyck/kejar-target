<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SalesActivity\CreateSalesActivityAction;
use App\Actions\SalesActivity\DeleteSalesActivityAction;
use App\Actions\SalesActivity\UpdateSalesActivityAction;
use App\Enums\UserRole;
use App\Http\Requests\SalesActivity\StoreSalesActivityRequest;
use App\Http\Requests\SalesActivity\UpdateSalesActivityRequest;
use App\Http\Resources\SalesActivityResource;
use App\Models\SalesActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesActivityController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', 'in:draft,pending,validated,rejected'],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $activities = $this->visibleQuery($request)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('activity_date', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('activity_date', '<=', $request->string('to')))
            ->with(['team:id,name', 'sales:id,name', 'product:id,name,code,is_active'])
            ->orderByDesc('activity_date')
            ->orderByDesc('created_at')
            ->paginate(min(100, max(1, $request->integer('per_page', 20))));

        return $this->paginated(
            $activities,
            SalesActivityResource::collection($activities->getCollection())->resolve(),
        );
    }

    public function store(StoreSalesActivityRequest $request, CreateSalesActivityAction $action): JsonResponse
    {
        $data = $request->safe()->except('evidence');
        $newEvidence = null;
        if ($request->hasFile('evidence')) {
            $newEvidence = $request->file('evidence')->store(
                'evidence/'.$request->user()->company_id,
                'local',
            );
            $data['evidence_path'] = $newEvidence;
        }

        try {
            $activity = $action->execute($request->user(), $data);
        } catch (\Throwable $exception) {
            if ($newEvidence) {
                Storage::disk('local')->delete($newEvidence);
            }

            throw $exception;
        }

        return $this->data((new SalesActivityResource($activity->load(['team', 'sales', 'product'])))->resolve(), 201);
    }

    public function show(Request $request, string $activity): JsonResponse
    {
        $model = $this->findVisible($request, $activity)->load(['team', 'sales', 'product']);

        return $this->data((new SalesActivityResource($model))->resolve());
    }

    public function update(
        UpdateSalesActivityRequest $request,
        string $activity,
        UpdateSalesActivityAction $action,
    ): JsonResponse {
        $model = $this->findVisible($request, $activity);
        abort_unless($model->sales_id === $request->user()->id, 403);
        $data = $request->safe()->except('evidence');
        $newEvidence = null;

        if ($request->hasFile('evidence')) {
            $newEvidence = $request->file('evidence')->store(
                'evidence/'.$request->user()->company_id,
                'local',
            );
            $data['evidence_path'] = $newEvidence;
        }

        try {
            $model = $action->execute($request->user(), $model, $data);
        } catch (\Throwable $exception) {
            if ($newEvidence) {
                Storage::disk('local')->delete($newEvidence);
            }

            throw $exception;
        }

        return $this->data((new SalesActivityResource($model->load(['team', 'sales', 'product'])))->resolve());
    }

    public function destroy(
        Request $request,
        string $activity,
        DeleteSalesActivityAction $action,
    ): Response {
        $model = $this->findVisible($request, $activity);
        abort_unless($model->sales_id === $request->user()->id, 403);
        $action->execute($request->user(), $model);

        return response()->noContent();
    }

    public function evidence(Request $request, string $activity): BinaryFileResponse
    {
        $model = $this->findVisible($request, $activity);
        abort_unless(
            $model->evidence_path && Storage::disk('local')->exists($model->evidence_path),
            404,
        );

        return response()->download(storage_path('app/private/'.$model->evidence_path));
    }

    private function findVisible(Request $request, string $id): SalesActivity
    {
        return $this->visibleQuery($request)->findOrFail($id);
    }

    private function visibleQuery(Request $request): Builder
    {
        $user = $request->user();

        return SalesActivity::query()
            ->where('company_id', $user->company_id)
            ->when($user->role === UserRole::SALES, fn ($query) => $query->where('sales_id', $user->id))
            ->when($user->role === UserRole::SPV, fn ($query) => $query->whereHas(
                'team',
                fn ($team) => $team->where('supervisor_id', $user->id),
            ));
    }
}
