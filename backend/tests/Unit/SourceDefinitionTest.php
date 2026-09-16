<?php
namespace Tests\Unit;
use Tests\TestCase;
use App\DTO\SourceDefinition;

class SourceDefinitionTest extends TestCase {
 public function test_source_requires_explicit_permission(): void {
  $s=SourceDefinition::fromArray('x',['name'=>'X','class'=>'public_html','url'=>'https://example.com','permission_confirmed'=>false]);
  $this->assertFalse($s->permissionConfirmed);
 }
}
