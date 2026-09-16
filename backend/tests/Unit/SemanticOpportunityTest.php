<?php
namespace Tests\Unit;
use Tests\TestCase;
use App\Services\SemanticOpportunityService;
class SemanticOpportunityTest extends TestCase {
 public function test_service_exists(): void { $this->assertInstanceOf(SemanticOpportunityService::class, app(SemanticOpportunityService::class)); }
 public function test_config_has_priority_commercial_terms(): void { $this->assertContains('FOB', config('semantic_matching.protected_terms',['FOB'])); }
}
