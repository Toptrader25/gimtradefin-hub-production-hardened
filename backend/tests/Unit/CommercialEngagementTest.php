<?php
namespace Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CommercialEngagementService;

class CommercialEngagementTest extends TestCase {
 use RefreshDatabase;
 public function test_opportunity_and_contact_can_be_created(): void { $s=app(CommercialEngagementService::class); $id=$s->createOpportunity(['title'=>'Vietnam buyer - edible oil','owner'=>'reviewer']); $this->assertNotEmpty($id); $cid=$s->addContact($id,['name'=>'Procurement Manager','email'=>'buyer@example.com']); $this->assertNotEmpty($cid); $snap=$s->snapshot($id); $this->assertCount(1,$snap['contacts']); }
 public function test_outcome_advances_pipeline(): void { $s=app(CommercialEngagementService::class); $id=$s->createOpportunity(['title'=>'Test']); $s->recordOutcome($id,'meeting_booked',80,'Meeting confirmed','reviewer'); $this->assertSame('meeting',$s->snapshot($id)['opportunity']['stage']); }
}
