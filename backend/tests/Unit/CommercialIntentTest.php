<?php
use PHPUnit\Framework\TestCase;
use App\Services\CommercialIntentService;

final class CommercialIntentTest extends TestCase {
 public function test_taxonomy_contains_explicit_buy_signal(): void {
  $signals=CommercialIntentService::signals();
  $this->assertArrayHasKey('buying',$signals);
  $this->assertArrayHasKey('explicit_buy_request',$signals['buying']);
  $this->assertGreaterThan(0,$signals['buying']['explicit_buy_request']['weight']);
 }
 public function test_intent_stage_boundaries_are_defined_in_documentation(): void {
  $doc=file_get_contents(__DIR__.'/../../docs/STAGE5_COMMERCIAL_INTENT.md');
  $this->assertStringContainsString('active_purchase',$doc);
  $this->assertStringContainsString('signal decay',$doc);
 }
}
