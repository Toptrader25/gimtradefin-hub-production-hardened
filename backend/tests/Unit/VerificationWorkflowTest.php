<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
use App\Services\VerificationWorkflowService;

class VerificationWorkflowTest extends TestCase
{
 public function test_approval_gate_requires_evidence_and_verification(): void { $ref=new \ReflectionClass(VerificationWorkflowService::class); $m=$ref->getMethod('canApprove'); $m->setAccessible(true); $this->assertFalse($m->invoke(new VerificationWorkflowService(),['case'=>['verification_score'=>90],'gates'=>['critical_unresolved'=>0,'required_failed'=>0,'evidence_ready'=>false]])); $this->assertTrue($m->invoke(new VerificationWorkflowService(),['case'=>['verification_score'=>90],'gates'=>['critical_unresolved'=>0,'required_failed'=>0,'evidence_ready'=>true]])); }
}
