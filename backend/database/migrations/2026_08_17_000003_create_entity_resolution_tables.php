<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('entity_type', 40)->index(); // organization, person, unknown
            $t->string('canonical_name', 500)->index();
            $t->string('legal_name', 500)->nullable();
            $t->string('country_code', 3)->nullable()->index();
            $t->string('country_name', 190)->nullable();
            $t->string('website_domain', 255)->nullable()->index();
            $t->string('status', 40)->default('active')->index();
            $t->decimal('resolution_confidence', 5, 4)->default(0);
            $t->string('resolution_state', 40)->default('unresolved')->index();
            $t->json('profile')->nullable();
            $t->timestamp('first_seen_at');
            $t->timestamp('last_seen_at');
            $t->timestamps();
        });

        Schema::create('entity_aliases', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('entity_id')->index();
            $t->string('alias', 500);
            $t->string('normalized_alias', 500)->index();
            $t->string('alias_type', 60)->default('name')->index();
            $t->string('source_slug', 190)->nullable()->index();
            $t->unsignedInteger('observations')->default(1);
            $t->timestamps();
            $t->unique(['entity_id', 'normalized_alias', 'alias_type']);
            $t->index(['normalized_alias', 'alias_type']);
        });

        Schema::create('entity_identifiers', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('entity_id')->index();
            $t->string('identifier_type', 80)->index(); // domain, email_domain, phone, registration_no, tax_id, lei, website, social, address_hash
            $t->string('normalized_value', 1000)->index();
            $t->string('display_value', 1000)->nullable();
            $t->string('source_slug', 190)->nullable()->index();
            $t->boolean('is_primary')->default(false);
            $t->boolean('is_verified')->default(false)->index();
            $t->decimal('confidence', 5, 4)->default(0);
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['identifier_type', 'normalized_value', 'entity_id']);
            $t->index(['identifier_type', 'normalized_value']);
        });

        Schema::create('entity_observations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('entity_id')->index();
            $t->uuid('candidate_id')->nullable()->index();
            $t->uuid('manifest_id')->nullable()->index();
            $t->string('source_slug', 190)->nullable()->index();
            $t->string('observed_name', 500)->nullable();
            $t->string('observed_country', 190)->nullable();
            $t->string('observed_domain', 255)->nullable();
            $t->string('observed_url', 2048)->nullable();
            $t->string('observed_address', 1000)->nullable();
            $t->string('observed_phone', 120)->nullable();
            $t->string('observed_email_domain', 255)->nullable();
            $t->json('observed_attributes')->nullable();
            $t->timestamp('observed_at');
            $t->timestamps();
        });

        Schema::create('entity_match_candidates', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('observation_id')->index();
            $t->uuid('entity_id')->index();
            $t->decimal('score', 6, 4)->index();
            $t->string('decision', 40)->default('review')->index(); // auto_link, review, reject
            $t->json('features')->nullable();
            $t->json('reasons')->nullable();
            $t->timestamp('created_at');
            $t->timestamp('updated_at')->nullable();
            $t->unique(['observation_id', 'entity_id']);
        });

        Schema::create('entity_links', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('entity_id')->index();
            $t->string('source_type', 80)->index();
            $t->uuid('source_id')->index();
            $t->string('link_type', 50)->default('resolved')->index();
            $t->decimal('confidence', 5, 4)->default(0);
            $t->string('resolution_method', 100)->nullable();
            $t->json('evidence')->nullable();
            $t->timestamp('created_at');
            $t->timestamp('updated_at')->nullable();
            $t->unique(['entity_id', 'source_type', 'source_id', 'link_type']);
        });

        Schema::create('entity_merge_events', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('surviving_entity_id')->index();
            $t->uuid('merged_entity_id')->index();
            $t->string('action', 40)->default('merge')->index();
            $t->decimal('confidence', 5, 4)->default(0);
            $t->string('actor_type', 40)->default('system');
            $t->string('actor_id', 190)->nullable();
            $t->text('reason')->nullable();
            $t->json('snapshot')->nullable();
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        foreach ([
            'entity_merge_events','entity_links','entity_match_candidates','entity_observations',
            'entity_identifiers','entity_aliases','entities'
        ] as $table) Schema::dropIfExists($table);
    }
};
