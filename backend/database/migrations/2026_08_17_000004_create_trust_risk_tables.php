<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('risk_signals', function(Blueprint $t){
            $t->uuid('id')->primary();
            $t->string('subject_type',60)->index(); // entity, candidate, observation
            $t->uuid('subject_id')->index();
            $t->string('signal_code',100)->index();
            $t->string('category',60)->index();
            $t->unsignedTinyInteger('severity')->default(1)->index(); // 1-5
            $t->decimal('confidence',5,4)->default(0);
            $t->string('status',30)->default('open')->index(); // open, dismissed, confirmed, expired
            $t->string('source_slug',190)->nullable()->index();
            $t->string('evidence_type',80)->nullable();
            $t->string('evidence_ref',255)->nullable();
            $t->json('details')->nullable();
            $t->timestamp('observed_at')->index();
            $t->timestamps();
            $t->index(['subject_type','subject_id','status']);
        });

        Schema::create('risk_assessments', function(Blueprint $t){
            $t->uuid('id')->primary();
            $t->string('subject_type',60)->index();
            $t->uuid('subject_id')->index();
            $t->unsignedTinyInteger('risk_score')->default(0)->index(); // 0-100
            $t->string('risk_band',30)->default('low')->index(); // low, guarded, high, critical
            $t->string('decision',40)->default('allow')->index(); // allow, review, restrict, suppress
            $t->json('dimensions')->nullable();
            $t->json('top_signals')->nullable();
            $t->timestamp('calculated_at')->index();
            $t->timestamps();
            $t->index(['subject_type','subject_id','calculated_at']);
        });

        Schema::create('opportunity_fingerprints', function(Blueprint $t){
            $t->uuid('id')->primary();
            $t->uuid('candidate_id')->unique();
            $t->string('fingerprint',128)->index();
            $t->string('title_fingerprint',128)->nullable()->index();
            $t->string('content_fingerprint',128)->nullable()->index();
            $t->string('entity_fingerprint',128)->nullable()->index();
            $t->string('product_fingerprint',128)->nullable()->index();
            $t->json('components')->nullable();
            $t->timestamp('created_at');
            $t->timestamp('updated_at')->nullable();
        });

        Schema::create('duplicate_clusters', function(Blueprint $t){
            $t->uuid('id')->primary();
            $t->string('cluster_type',40)->index(); // exact, near_duplicate, recurring
            $t->string('status',30)->default('open')->index();
            $t->uuid('primary_candidate_id')->nullable()->index();
            $t->unsignedInteger('member_count')->default(0);
            $t->decimal('confidence',5,4)->default(0);
            $t->json('explanation')->nullable();
            $t->timestamps();
        });

        Schema::create('duplicate_cluster_members', function(Blueprint $t){
            $t->uuid('id')->primary();
            $t->uuid('cluster_id')->index();
            $t->uuid('candidate_id')->index();
            $t->decimal('similarity',5,4)->default(0);
            $t->string('membership_type',40)->default('suspected');
            $t->json('features')->nullable();
            $t->timestamps();
            $t->unique(['cluster_id','candidate_id']);
        });

        Schema::create('risk_review_cases', function(Blueprint $t){
            $t->uuid('id')->primary();
            $t->string('subject_type',60)->index();
            $t->uuid('subject_id')->index();
            $t->string('case_type',60)->index();
            $t->string('priority',30)->default('medium')->index();
            $t->string('status',30)->default('open')->index();
            $t->string('assigned_to',190)->nullable()->index();
            $t->text('reason')->nullable();
            $t->json('evidence')->nullable();
            $t->text('resolution_note')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('risk_rules', function(Blueprint $t){
            $t->uuid('id')->primary();
            $t->string('code',100)->unique();
            $t->string('category',60)->index();
            $t->unsignedTinyInteger('severity')->default(2);
            $t->boolean('enabled')->default(true)->index();
            $t->json('parameters')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        foreach(['risk_rules','risk_review_cases','duplicate_cluster_members','duplicate_clusters','opportunity_fingerprints','risk_assessments','risk_signals'] as $t) Schema::dropIfExists($t);
    }
};
