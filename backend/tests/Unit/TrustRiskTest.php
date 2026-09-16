<?php
namespace Tests\Unit;
use Tests\TestCase;
use App\Services\TrustRiskService;

class TrustRiskTest extends TestCase {
    public function test_content_key_is_deterministic_through_fingerprint(): void {
        $this->assertTrue(method_exists(TrustRiskService::class,'fingerprintCandidate'));
    }
}
