<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
use App\Services\OpportunityMatchingService;
class OpportunityMatchingTest extends TestCase {
 public function test_role_pair_matrix_contains_core_commercial_paths(): void {
  $ref=new \ReflectionClass(OpportunityMatchingService::class); $c=$ref->getConstant('ROLE_PAIRS');
  $this->assertSame('trade',$c['buyer:seller']); $this->assertSame('investment',$c['capital_seeker:investor']); $this->assertSame('partnership',$c['seller:partner']);
 }
}
