<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('evidence_manifests', function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->uuid('run_id')->index();
   $t->uuid('payload_id')->index();
   $t->string('source_slug')->index();
   $t->string('canonical_url',2048);
   $t->unsignedSmallInteger('http_status')->nullable();
   $t->string('content_type',190)->nullable();
   $t->char('payload_sha256',64)->index();
   $t->unsignedInteger('payload_bytes')->default(0);
   $t->timestamp('captured_at');
   $t->string('retrieval_method',100)->default('http');
   $t->string('policy_status',50)->default('permitted');
   $t->json('headers')->nullable();
   $t->timestamps();
   $t->unique(['run_id','payload_id']);
  });

  Schema::create('candidate_evidence', function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->uuid('candidate_id')->index();
   $t->uuid('manifest_id')->index();
   $t->string('field_name',100)->index();
   $t->text('field_value')->nullable();
   $t->string('locator',2048)->nullable();
   $t->text('excerpt')->nullable();
   $t->char('excerpt_sha256',64)->nullable();
   $t->string('verification_status',40)->default('unverified')->index();
   $t->string('verification_method',100)->nullable();
   $t->decimal('confidence',5,4)->nullable();
   $t->timestamp('captured_at');
   $t->timestamps();
   $t->index(['candidate_id','field_name']);
  });

  Schema::create('evidence_integrity_checks', function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->uuid('manifest_id')->index();
   $t->string('check_type',100);
   $t->string('status',40)->index();
   $t->text('details')->nullable();
   $t->timestamp('checked_at');
   $t->timestamps();
  });
 }
 public function down(): void {
  foreach(['evidence_integrity_checks','candidate_evidence','evidence_manifests'] as $t) Schema::dropIfExists($t);
 }
};
