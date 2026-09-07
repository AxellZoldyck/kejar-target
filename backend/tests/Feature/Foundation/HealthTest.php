<?php

namespace Tests\Feature\Foundation;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_versioned_health_endpoint_is_available_without_authentication(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonStructure([
                'data' => ['status', 'service', 'timestamp'],
            ]);
    }
}
