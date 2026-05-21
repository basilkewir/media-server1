<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AccessCode;
use App\Models\CodeRedemption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_validate_a_valid_code(): void
    {
        $code = AccessCode::factory()->create(['uses_count' => 0, 'max_uses' => 5]);

        $response = $this->withApiToken()->postJson('/api/access-codes/validate', [
            'code' => $code->code,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.code', $code->code);
    }

    public function test_validation_fails_for_expired_code(): void
    {
        $code = AccessCode::factory()->expired()->create();

        $response = $this->withApiToken()->postJson('/api/access-codes/validate', [
            'code' => $code->code,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error_code', 'INVALID_ACCESS_CODE');
    }

    public function test_validation_rejects_short_code(): void
    {
        $response = $this->withApiToken()->postJson('/api/access-codes/validate', [
            'code' => 'SHORT',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_can_redeem_code(): void
    {
        $code = AccessCode::factory()->create([
            'duration_days' => 30,
            'uses_count' => 0,
            'max_uses' => 1,
        ]);

        $response = $this->withApiToken()->postJson('/api/access-codes/redeem', [
            'code' => $code->code,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', $code->type)
            ->assertJsonPath('data.already_redeemed', false);

        $this->assertDatabaseHas('code_redemptions', [
            'access_code_id' => $code->id,
            'is_active' => true,
        ]);

        $code->refresh();
        $this->assertEquals(1, $code->uses_count);
    }

    public function test_redeeming_fully_used_code_fails(): void
    {
        $code = AccessCode::factory()->fullyRedeemed()->create();

        $response = $this->withApiToken()->postJson('/api/access-codes/redeem', [
            'code' => $code->code,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error_code', 'INVALID_ACCESS_CODE');
    }

    public function test_same_ip_redeeming_again_returns_active_status(): void
    {
        $code = AccessCode::factory()->create(['max_uses' => 5]);

        // First redemption
        $this->withApiToken()->postJson('/api/access-codes/redeem', ['code' => $code->code]);

        // Second redemption from same IP
        $response = $this->withApiToken()->postJson('/api/access-codes/redeem', [
            'code' => $code->code,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.already_redeemed', true);
    }

    public function test_can_check_status_with_active_redemption(): void
    {
        $redemption = CodeRedemption::factory()->create([
            'ip_address' => '127.0.0.1',
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withApiToken()->getJson('/api/access-codes/status');

        $response->assertStatus(200)
            ->assertJsonPath('data.active', true)
            ->assertJsonCount(1, 'data.subscriptions');
    }

    public function test_status_returns_inactive_when_no_redemptions(): void
    {
        $response = $this->withApiToken()->getJson('/api/access-codes/status');

        $response->assertStatus(200)
            ->assertJsonPath('data.active', false);
    }

    public function test_generate_batch_creates_correct_quantity(): void
    {
        $codes = AccessCode::generateBatch('full_access', 365, 10);

        $this->assertCount(10, $codes);
        $this->assertDatabaseCount('access_codes', 10);

        foreach ($codes as $code) {
            $this->assertGreaterThanOrEqual(8, strlen(str_replace('-', '', $code->code)));
            $this->assertEquals('full_access', $code->type);
            $this->assertEquals(365, $code->duration_days);
        }
    }
}
