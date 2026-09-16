<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('multilingual_text_assets', function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->string('asset_type',40)->index(); // candidate,evidence,entity_fact,source
   $t->uuid('asset_id')->index();
   $t->longText('original_text');
   $t->string('source_language',16)->nullable()->index();
   $t->decimal('language_confidence',6,5)->default(0);
   $t->string('script',32)->nullable()->index();
   $t->boolean('mixed_language')->default(false);
   $t->string('normalization_version',40)->nullable();
   $t->json('metadata')->nullable();
   $t->timestamps();
   $t->index(['asset_type','asset_id']);
  });
  Schema::create('multilingual_translations', function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->uuid('text_asset_id')->index();
   $t->string('target_language',16)->index();
   $t->longText('translated_text');
   $t->string('provider',80);
   $t->string('model',120)->nullable();
   $t->string('translation_version',60)->nullable();
   $t->decimal('quality_confidence',6,5)->default(0);
   $t->json('quality_checks')->nullable();
   $t->timestamp('created_at');
   $t->unique(['text_asset_id','target_language','provider','model']);
  });
  Schema::create('multilingual_concepts', function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->uuid('text_asset_id')->index();
   $t->string('concept_type',60)->index(); // buying,selling,partnership,investment,product,industry,etc
   $t->string('canonical_key',160)->index();
   $t->string('matched_term',255);
   $t->string('language',16)->index();
   $t->decimal('confidence',6,5)->default(0);
   $t->json('span')->nullable();
   $t->timestamps();
   $t->index(['text_asset_id','concept_type']);
  });
  Schema::create('multilingual_glossary_terms', function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->string('canonical_key',160)->index();
   $t->string('concept_type',60)->index();
   $t->string('language',16)->index();
   $t->string('term',255)->index();
   $t->string('normalized_term',255)->index();
   $t->boolean('protected')->default(false);
   $t->json('metadata')->nullable();
   $t->timestamps();
   $t->unique(['canonical_key','language','normalized_term']);
  });
  Schema::create('multilingual_quality_checks', function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->uuid('translation_id')->index();
   $t->string('check_type',60)->index();
   $t->boolean('passed')->default(false);
   $t->decimal('score',6,5)->default(0);
   $t->json('details')->nullable();
   $t->timestamp('checked_at');
  });
 }
 public function down(): void { foreach(['multilingual_quality_checks','multilingual_glossary_terms','multilingual_concepts','multilingual_translations','multilingual_text_assets'] as $t) Schema::dropIfExists($t); }
};
