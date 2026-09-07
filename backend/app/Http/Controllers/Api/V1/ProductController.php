<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Commission\ReviseCommissionSettingAction;
use App\Enums\UserRole;
use App\Http\Requests\Organization\StoreProductRequest;
use App\Http\Requests\Organization\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\CommissionSetting;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'active' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $products = Product::query()
            ->where('company_id', $request->user()->company_id)
            ->when($request->user()->role === UserRole::SALES, fn ($query) => $query->where('is_active', true))
            ->when($request->has('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate(min(100, max(1, $request->integer('per_page', 50))));

        return $this->paginated($products, ProductResource::collection($products->getCollection())->resolve());
    }

    public function store(
        StoreProductRequest $request,
        ReviseCommissionSettingAction $reviseCommissionSetting,
    ): JsonResponse {
        $product = DB::transaction(function () use ($request, $reviseCommissionSetting): Product {
            Company::query()->lockForUpdate()->findOrFail($request->user()->company_id);
            $product = Product::query()->create([
                ...$request->safe()->only(['name', 'code', 'product_fee_amount']),
                'company_id' => $request->user()->company_id,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $current = $this->currentCommissionSetting($request);
            if ($current) {
                $fees = $current->productFees
                    ->map(fn ($row) => $row->only(['product_id', 'fee_amount']))
                    ->push([
                        'product_id' => $product->id,
                        'fee_amount' => $product->product_fee_amount,
                    ])
                    ->values()
                    ->all();
                $reviseCommissionSetting->execute($request->user(), fees: $fees);
            }

            return $product;
        });

        return $this->data((new ProductResource($product))->resolve(), 201);
    }

    public function update(
        UpdateProductRequest $request,
        string $product,
        ReviseCommissionSettingAction $reviseCommissionSetting,
    ): JsonResponse {
        $model = DB::transaction(function () use ($request, $product, $reviseCommissionSetting): Product {
            Company::query()->lockForUpdate()->findOrFail($request->user()->company_id);
            $model = Product::query()
                ->where('company_id', $request->user()->company_id)
                ->lockForUpdate()
                ->findOrFail($product);
            $oldFee = (int) $model->product_fee_amount;
            $model->update($request->validated());

            if ($request->exists('product_fee_amount')
                && $oldFee !== (int) $model->product_fee_amount) {
                $current = $this->currentCommissionSetting($request);
                if ($current) {
                    $fees = $current->productFees
                        ->map(fn ($row) => [
                            'product_id' => $row->product_id,
                            'fee_amount' => $row->product_id === $model->id
                                ? (int) $model->product_fee_amount
                                : (int) $row->fee_amount,
                        ])
                        ->values()
                        ->all();
                    $reviseCommissionSetting->execute($request->user(), fees: $fees);
                }
            }

            return $model;
        });

        return $this->data((new ProductResource($model->refresh()))->resolve());
    }

    private function currentCommissionSetting(Request $request): ?CommissionSetting
    {
        return CommissionSetting::query()
            ->where('company_id', $request->user()->company_id)
            ->where('active_slot', 'active')
            ->with('productFees')
            ->lockForUpdate()
            ->first();
    }
}
