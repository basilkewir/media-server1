<?php

declare(strict_types=1);

namespace Tests;

use App\Models\ApiToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $apiToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test API token for authenticated requests
        $token = ApiToken::generate('Test Token');
        $this->apiToken = $token->plain_token;
    }

    protected function withApiToken(): static
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->apiToken);
    }

    protected function assertApiSuccessStructure(array $response): void
    {
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
    }

    protected function assertApiErrorStructure(array $response, int $expectedStatus = 400): void
    {
        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('error_code', $response);
    }
}
