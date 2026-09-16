<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->enum('category', ['buying', 'selling', 'partnership']);
            $table->text('description');

            // Structured deal fields (used by the matching engine later)
            $table->string('quantity')->nullable();
            $table->string('country');
            $table->string('preferred_origin')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('industry')->nullable();

            // Lifecycle status — section 11. Never skip straight to "published".
            // test        = synthetic dev-only record, never shown publicly
            // discovered  = found by a source connector, unreviewed
            // reviewing   = GiMtradefin is investigating it
            // verified    = independently verified, not yet public
            // published   = approved for public display
            // expired     = no longer active
            // rejected    = failed verification
            $table->enum('status', [
                'test', 'discovered', 'reviewing', 'verified', 'published', 'expired', 'rejected',
            ])->default('discovered');

            // Relationships
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Scoring — section 15. Nullable because scoring happens after
            // AI analysis, not at creation time. Never fake a number here.
            $table->unsignedTinyInteger('commercial_intent_score')->nullable();
            $table->unsignedTinyInteger('source_reliability_score')->nullable();
            $table->unsignedTinyInteger('evidence_strength_score')->nullable();
            $table->unsignedTinyInteger('recency_score')->nullable();
            $table->unsignedTinyInteger('company_confidence_score')->nullable();
            $table->unsignedTinyInteger('match_potential_score')->nullable();
            $table->unsignedTinyInteger('overall_score')->nullable();

            // Verification — section 20. AI never marks something verified;
            // it can only recommend. A human reviewer owns this field.
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'status']);
            $table->index('country');
            $table->index('overall_score');
        });
    }

    public function down(): void {
        Schema::dropIfExists('opportunities');
    }
};
