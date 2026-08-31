<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_the_api_health_check_responds_successfully(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()->assertExactJson([
            'status' => 'ok',
            'service' => 'happyro-admin-backend',
        ]);
    }
}
