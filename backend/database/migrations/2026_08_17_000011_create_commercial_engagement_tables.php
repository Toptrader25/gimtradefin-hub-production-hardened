<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('engagement_opportunities', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('entity_id')->nullable(); $t->uuid('match_id')->nullable(); $t->uuid('verification_case_id')->nullable();
   $t->string('title'); $t->string('stage')->default('approved'); $t->string('status')->default('open'); $t->string('owner')->nullable();
   $t->unsignedTinyInteger('priority')->default(50); $t->string('next_action')->nullable(); $t->timestamp('next_action_at')->nullable();
   $t->json('commercial_context')->nullable(); $t->timestamps();
   $t->index(['status','stage']); $t->index(['owner','status']);
  });
  Schema::create('engagement_contacts', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('opportunity_id'); $t->uuid('entity_id')->nullable(); $t->string('name')->nullable(); $t->string('role')->nullable();
   $t->string('email')->nullable(); $t->string('phone')->nullable(); $t->string('channel')->nullable(); $t->boolean('verified')->default(false); $t->string('verification_level')->default('unverified');
   $t->json('evidence_refs')->nullable(); $t->timestamps(); $t->index(['opportunity_id','verified']);
  });
  Schema::create('engagement_activities', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('opportunity_id'); $t->string('type'); $t->string('direction')->default('internal'); $t->string('actor')->nullable();
   $t->string('channel')->nullable(); $t->string('subject')->nullable(); $t->text('body')->nullable(); $t->string('outcome')->nullable(); $t->json('metadata')->nullable(); $t->timestamp('occurred_at'); $t->timestamps();
   $t->index(['opportunity_id','occurred_at']);
  });
  Schema::create('engagement_tasks', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('opportunity_id'); $t->string('task_type'); $t->string('status')->default('open'); $t->string('assignee')->nullable();
   $t->string('title'); $t->text('notes')->nullable(); $t->timestamp('due_at')->nullable(); $t->timestamp('completed_at')->nullable(); $t->timestamps(); $t->index(['assignee','status','due_at']);
  });
  Schema::create('engagement_messages', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('opportunity_id'); $t->uuid('contact_id')->nullable(); $t->string('channel'); $t->string('direction'); $t->string('status')->default('draft');
   $t->string('subject')->nullable(); $t->longText('body'); $t->string('provider')->nullable(); $t->string('provider_message_id')->nullable(); $t->json('metadata')->nullable(); $t->timestamp('sent_at')->nullable(); $t->timestamps();
   $t->index(['opportunity_id','status']);
  });
  Schema::create('engagement_outcomes', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('opportunity_id'); $t->string('outcome'); $t->unsignedTinyInteger('score')->nullable(); $t->text('notes')->nullable(); $t->string('recorded_by'); $t->timestamp('occurred_at'); $t->timestamps();
   $t->index(['opportunity_id','occurred_at']);
  });
 }
 public function down(): void { foreach(['engagement_outcomes','engagement_messages','engagement_tasks','engagement_activities','engagement_contacts','engagement_opportunities'] as $t) Schema::dropIfExists($t); }
};
