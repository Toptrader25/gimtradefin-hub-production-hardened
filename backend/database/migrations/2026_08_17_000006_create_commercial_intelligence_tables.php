<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('entity_commercial_profiles', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('entity_id')->unique()->index();
   $t->string('primary_role',40)->nullable()->index(); // buyer,seller,partner,investor,capital_seeker,hybrid
   $t->json('roles')->nullable(); $t->json('industries')->nullable(); $t->json('products')->nullable();
   $t->json('markets')->nullable(); $t->json('capabilities')->nullable(); $t->json('commercial_terms')->nullable();
   $t->json('investment_profile')->nullable(); $t->json('counterparty_summary')->nullable();
   $t->unsignedTinyInteger('profile_confidence')->default(0); $t->timestamp('profiled_at'); $t->timestamps();
  });
  Schema::create('entity_commercial_facts', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('entity_id')->index();
   $t->string('fact_type',80)->index(); $t->string('fact_key',120)->index(); $t->text('fact_value')->nullable();
   $t->string('normalized_value',500)->nullable()->index(); $t->string('unit',50)->nullable();
   $t->decimal('confidence',5,4)->default(0); $t->string('source_slug',190)->nullable()->index();
   $t->uuid('candidate_id')->nullable()->index(); $t->uuid('evidence_id')->nullable()->index();
   $t->timestamp('observed_at')->index(); $t->timestamp('expires_at')->nullable(); $t->json('metadata')->nullable(); $t->timestamps();
   $t->index(['entity_id','fact_type','fact_key']);
  });
  Schema::create('entity_commercial_roles', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('entity_id')->index(); $t->string('role',40)->index();
   $t->decimal('score',6,4)->default(0); $t->string('basis',80)->nullable(); $t->json('signals')->nullable();
   $t->timestamp('calculated_at')->index(); $t->timestamps();
   $t->unique(['entity_id','role','calculated_at']);
  });
  Schema::create('entity_relationships', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('from_entity_id')->index(); $t->uuid('to_entity_id')->index();
   $t->string('relationship_type',80)->index(); $t->decimal('confidence',5,4)->default(0);
   $t->string('source_slug',190)->nullable()->index(); $t->uuid('candidate_id')->nullable()->index(); $t->uuid('evidence_id')->nullable()->index();
   $t->json('details')->nullable(); $t->timestamp('observed_at')->index(); $t->timestamps();
   $t->unique(['from_entity_id','to_entity_id','relationship_type','source_slug']);
  });
  Schema::create('entity_profile_snapshots', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('entity_id')->index(); $t->string('profile_version',40);
   $t->json('profile'); $t->unsignedTinyInteger('confidence')->default(0); $t->timestamp('created_at');
  });
 }
 public function down(): void { foreach(['entity_profile_snapshots','entity_relationships','entity_commercial_roles','entity_commercial_facts','entity_commercial_profiles'] as $t) Schema::dropIfExists($t); }
};
