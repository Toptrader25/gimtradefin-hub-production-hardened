<?php
use PHPUnit\Framework\TestCase;
class CommercialIntelligenceTest extends TestCase {
 public function test_roles_are_distinct_concepts(): void { $roles=['buyer','seller','partner','investor','capital_seeker']; $this->assertCount(5,array_unique($roles)); $this->assertContains('investor',$roles); $this->assertContains('capital_seeker',$roles); }
 public function test_investment_and_capital_seeking_are_not_the_same_role(): void { $this->assertNotSame('investor','capital_seeker'); }
}
