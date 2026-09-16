<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('match_runs', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->string('run_type',60)->default('pair_generation')->index();
   $t->string('status',30)->default('completed')->index(); $t->unsignedInteger('pairs_evaluated')->default(0);
   $t->unsignedInteger('matches_created')->default(0); $t->json('parameters')->nullable(); $t->timestamp('started_at'); $t->timestamp('finished_at')->nullable(); $t->timestamps();
  });
  Schema::create('opportunity_matches', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('run_id')->nullable()->index();
   $t->uuid('left_entity_id')->index(); $t->uuid('right_entity_id')->index();
   $t->string('left_role',40)->index(); $t->string('right_role',40)->index();
   $t->string('match_type',50)->index();
   $t->unsignedTinyInteger('match_score')->default(0)->index();
   $t->unsignedTinyInteger('confidence')->default(0)->index();
   $t->string('recommendation',30)->default('review')->index();
   $t->string('status',30)->default('candidate')->index();
   $t->json('dimensions')->nullable(); $t->json('evidence')->nullable(); $t->json('gaps')->nullable();
   $t->json('risks')->nullable(); $t->json('explanation')->nullable();
   $t->timestamp('calculated_at')->index(); $t->timestamps();
   $t->unique(['left_entity_id','right_entity_id','match_type']);
  });
  Schema::create('match_feedback', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('match_id')->index(); $t->string('decision',40)->index();
   $t->unsignedTinyInteger('actual_quality')->nullable(); $t->text('notes')->nullable(); $t->string('reviewer',120)->nullable();
   $t->timestamp('created_at')->index();
  });
 } public function down(): void { foreach(['match_feedback','opportunity_matches','match_runs'] as $t) Schema::dropIfExists($t); }
};
