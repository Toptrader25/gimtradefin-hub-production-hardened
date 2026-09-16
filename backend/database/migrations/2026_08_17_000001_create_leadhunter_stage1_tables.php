<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('source_registry',function(Blueprint $t){$t->id();$t->string('slug')->unique();$t->string('name');$t->string('connector');$t->string('url',2048);$t->string('region')->nullable();$t->string('role')->default('commercial');$t->unsignedInteger('priority')->default(50);$t->boolean('enabled')->default(false);$t->boolean('permission_confirmed')->default(false);$t->boolean('requires_auth')->default(false);$t->string('terms_url',2048)->nullable();$t->json('policy')->nullable();$t->timestamp('last_success_at')->nullable();$t->timestamp('last_failure_at')->nullable();$t->text('last_error')->nullable();$t->timestamps();});
  Schema::create('ingestion_runs',function(Blueprint $t){$t->uuid('id')->primary();$t->string('source_slug')->index();$t->string('status')->index();$t->unsignedSmallInteger('http_status')->nullable();$t->unsignedInteger('records_seen')->default(0);$t->unsignedInteger('new_records')->default(0);$t->unsignedInteger('duration_ms')->nullable();$t->text('error_message')->nullable();$t->timestamp('started_at');$t->timestamp('completed_at')->nullable();$t->timestamps();});
  Schema::create('source_payloads',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('run_id')->index();$t->string('source_slug')->index();$t->string('url',2048);$t->unsignedSmallInteger('http_status')->nullable();$t->string('content_type',190)->nullable();$t->char('sha256',64)->index();$t->string('etag',500)->nullable();$t->string('last_modified',500)->nullable();$t->longText('body');$t->timestamp('fetched_at');$t->timestamps();});
  Schema::create('source_candidates',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('run_id')->index();$t->string('source_slug')->index();$t->string('external_key',191);$t->string('title',500);$t->string('url',2048)->nullable();$t->text('description')->nullable();$t->string('country',190)->nullable();$t->string('signal_type',100)->nullable();$t->timestamp('published_at')->nullable();$t->json('attributes')->nullable();$t->timestamp('first_seen_at');$t->timestamp('last_seen_at');$t->timestamps();$t->unique(['source_slug','external_key']);});
  Schema::create('source_evidence',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('candidate_id')->index();$t->uuid('payload_id')->nullable()->index();$t->string('evidence_type',100);$t->string('locator',2048)->nullable();$t->text('excerpt')->nullable();$t->char('content_sha256',64)->nullable();$t->timestamp('captured_at');$t->timestamps();});
 }
 public function down(): void {foreach(['source_evidence','source_candidates','source_payloads','ingestion_runs','source_registry'] as $t) Schema::dropIfExists($t);}
};
