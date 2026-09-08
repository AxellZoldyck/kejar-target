<?php

namespace Tests\Feature\Foundation;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ApiErrorHandlingTest extends TestCase
{
    public function test_unexpected_api_exception_uses_generic_structured_response(): void
    {
        Route::get('/api/v1/_test/internal-error', function (): never {
            throw new RuntimeException('detail sensitif tidak boleh bocor');
        });

        $this->getJson('/api/v1/_test/internal-error')
            ->assertInternalServerError()
            ->assertExactJson([
                'message' => 'Terjadi kesalahan internal.',
                'code' => 'INTERNAL_ERROR',
                'errors' => [],
            ])
            ->assertJsonMissing(['detail sensitif tidak boleh bocor']);
    }
}
