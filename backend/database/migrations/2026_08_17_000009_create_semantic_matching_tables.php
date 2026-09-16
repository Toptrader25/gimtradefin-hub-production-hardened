<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('semantic_entity_profiles', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('entity_id')->unique()->index();
   $t->json('products')->nullable(); $t->json('industries')->nullable(); $t->json('markets')->nullable(); $t->json('terms')->nullable();
   $t->json('capabilities')->nullable(); $t->json('hs_codes')->nullable(); $t->json('quantities')->nullable(); $t->json('prices')->nullable();
   $t->string('normalizer_version',30); $t->timestamp('calculated_at')->index(); $t->timestamps();
  });
  Schema::create('semantic_match_features', function(Blueprint $t){
   $t->uuid('id')->primary(); $t->uuid('match_id')->index();
   $t->unsignedTinyInteger('product_score')->default(0); $t->unsignedTinyInteger('industry_score')->default(0);
   $t->unsignedTinyInteger('market_score')->default(0); $t->unsignedTinyInteger('capability_score')->default(0);
   $t->unsignedTinyInteger('terms_score')->default(0); $t->unsignedTinyInteger('quantity_score')->default(0);
   $t->unsignedTinyInteger('price_score')->default(0); $t->unsignedTinyInteger('route_score')->default(0);
   $t->json('normalized_evidence')->nullable(); $t->string('semantic_provider',50)->default('deterministic');
   $t->string('semantic_version',30); $t->timestamp('calculated_at')->index(); $t->timestamps();
   $t->unique('match_id');
  });
  Schema::create('semantic_taxonomy_versions', function(Blueprint $t){
   $t->id(); $t->string('version',40)->unique(); $t->string('status',20)->default('active')->index(); $t->json('metadata')->nullable(); $t->timestamp('activated_at')->nullable(); $t->timestamps();
  });
 } public function down(): void { foreach(['semantic_taxonomy_versions','semantic_match_features','semantic_entity_profiles'] as $t) Schema::dropIfExists($t); }
};
