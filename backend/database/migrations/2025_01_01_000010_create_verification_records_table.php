<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('verification_records', function (Blueprint $table) {
            $table->id();
            $table->morphs('verifiable'); // polymorphic: can verify a Company OR an Opportunity
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Section 20 — the reviewer's decision. AI analysis feeds into
            // this, but AI is never allowed to set this field itself.
            $table->enum('decision', ['approved', 'rejected', 'more_evidence_requested', 'suspicious', 'expired'])
                  ->nullable();
            $table->text('ai_analysis_summary')->nullable(); // what the AI engine found, for the reviewer to read
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('verification_records');
    }
};
