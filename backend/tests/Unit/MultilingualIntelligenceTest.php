<?php
namespace Tests\Unit;
use Tests\TestCase;
use App\Services\MultilingualIntelligenceService;

class MultilingualIntelligenceTest extends TestCase {
 public function test_service_class_exists(): void { $this->assertTrue(class_exists(MultilingualIntelligenceService::class)); }
 public function test_config_contains_priority_languages(): void { $this->assertContains('zh',config('multilingual.supported_languages')); $this->assertContains('vi',config('multilingual.supported_languages')); $this->assertContains('ar',config('multilingual.supported_languages')); }
}
