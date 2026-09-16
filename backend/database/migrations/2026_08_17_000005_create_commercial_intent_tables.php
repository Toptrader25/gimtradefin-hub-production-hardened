<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('intent_events', function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->string('subject_type',60)->index();
   $t->uuid('subject_id')->index();
   $t->string('intent_type',100)->index();
   $t->string('signal_family',40)->index();
   $t->smallInteger('polarity')->default(1);
   $t->unsignedSmallInteger('base_weight')->default(1);
   $t->decimal('confidence',5,4)->default(0);
   $t->string('source_slug',190)->nullable()->index();
   $t->string('evidence_type',100)->nullable();
   $t->string('evidence_ref',255)->nullable();
   $t->uuid('candidate_id')->nullable()->index();
   $t->timestamp('occurred_at')->index();
   $t->timestamp('expires_at')->nullable()->index();
   $t->json('details')->nullable();
   $t->timestamps();
   $t->index(['subject_type','subject_id','occurred_at']);
  });

  Schema::create('intent_assessments', function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->string('subject_type',60)->index();
   $t->uuid('subject_id')->index();
   $t->unsignedTinyInteger('intent_score')->default(0)->index();
   $t->string('intent_stage',40)->index();
   $t->unsignedTinyInteger('buying_temperature')->default(0)->index();
   $t->decimal('confidence',5,4)->default(0);
   $t->json('dimensions')->nullable();
   $t->json('top_signals')->nullable();
   $t->unsignedSmallInteger('independent_source_count')->default(0);
   $t->timestamp('calculated_at')->index();
   $t->timestamps();
   $t->index(['subject_type','subject_id','calculated_at']);
  });

  Schema::create('intent_taxonomy_versions', function(Blueprint $t){
   $t->id();
   $t->string('version',40)->unique();
   $t->json('taxonomy');
   $t->boolean('active')->default(false)->index();
   $t->timestamps();
  });
 }
 public function down(): void { foreach(['intent_taxonomy_versions','intent_assessments','intent_events'] as $t) Schema::dropIfExists($t); }
};
