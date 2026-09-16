<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Tracks which Lead Hunter publication_decisions have already been
        // synced into the public-facing opportunities table, so re-running
        // the sync is idempotent. Deliberately a standalone table rather
        // than a column added to the Lead Hunter package's own tables —
        // keeps that package's schema untouched for easier upstream updates.
        Schema::create('lead_hunter_bridge_syncs', function (Blueprint $table) {
            $table->id();
            $table->uuid('publication_decision_id')->unique();
            $table->uuid('verification_case_id')->nullable();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->string('mapped_category')->nullable();
            $table->string('mapped_from_signal_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('lead_hunter_bridge_syncs');
    }
};
