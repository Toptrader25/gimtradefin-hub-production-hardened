<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('verification_cases', function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->uuid('entity_id')->nullable()->index();
   $t->uuid('match_id')->nullable()->index();
   $t->uuid('candidate_id')->nullable()->index();
   $t->string('case_type',40)->default('opportunity')->index();
   $t->string('status',30)->default('queued')->index();
   $t->string('priority',20)->default('normal')->index();
   $t->unsignedTinyInteger('verification_score')->default(0)->index();
   $t->unsignedTinyInteger('qualification_score')->default(0)->index();
   $t->string('decision',40)->default('pending')->index();
   $t->string('assigned_to',120)->nullable()->index();
   $t->timestamp('due_at')->nullable()->index();
   $t->timestamp('started_at')->nullable(); $t->timestamp('completed_at')->nullable();
   $t->json('context')->nullable(); $t->json('summary')->nullable();
   $t->timestamps();
  });
  Schema::create('verification_checks', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('case_id')->index();
   $t->string('check_code',70)->index(); $t->string('category',40)->index();
   $t->string('status',25)->default('pending')->index();
   $t->unsignedTinyInteger('weight')->default(1); $t->unsignedTinyInteger('score')->nullable();
   $t->string('source_type',40)->nullable(); $t->string('source_ref',255)->nullable();
   $t->text('finding')->nullable(); $t->json('evidence_refs')->nullable(); $t->json('required_actions')->nullable();
   $t->string('reviewer',120)->nullable(); $t->timestamp('verified_at')->nullable(); $t->timestamps();
   $t->unique(['case_id','check_code']);
  });
  Schema::create('verification_events', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('case_id')->index();
   $t->string('event_type',60)->index(); $t->string('actor_type',30)->default('system'); $t->string('actor',120)->nullable();
   $t->json('payload')->nullable(); $t->timestamp('created_at')->index();
  });
  Schema::create('qualification_answers', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('case_id')->index();
   $t->string('question_code',70)->index(); $t->text('answer')->nullable();
   $t->string('answer_type',30)->default('text'); $t->unsignedTinyInteger('score')->nullable();
   $t->string('source',40)->default('reviewer'); $t->string('reviewer',120)->nullable(); $t->timestamp('created_at')->index();
   $t->unique(['case_id','question_code']);
  });
  Schema::create('publication_decisions', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('case_id')->index();
   $t->string('decision',40)->index(); $t->string('visibility',30)->default('internal');
   $t->string('reason_code',80)->nullable(); $t->text('reason')->nullable(); $t->string('decided_by',120)->nullable();
   $t->json('conditions')->nullable(); $t->timestamp('decided_at')->index();
  });
 }
 public function down(): void { foreach(['publication_decisions','qualification_answers','verification_events','verification_checks','verification_cases'] as $t) Schema::dropIfExists($t); }
};
