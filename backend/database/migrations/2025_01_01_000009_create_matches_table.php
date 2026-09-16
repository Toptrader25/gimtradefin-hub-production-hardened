<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matched_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('matched_opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();

            // Explanation scores — section 16. A match is never just a
            // number; it must be able to explain *why*.
            $table->unsignedTinyInteger('product_compatibility')->nullable();
            $table->unsignedTinyInteger('market_compatibility')->nullable();
            $table->unsignedTinyInteger('industry_compatibility')->nullable();
            $table->unsignedTinyInteger('geographic_fit')->nullable();
            $table->unsignedTinyInteger('commercial_intent')->nullable();
            $table->unsignedTinyInteger('overall_match')->nullable();
            $table->text('explanation')->nullable(); // human-readable "why this match was suggested"

            $table->string('status')->default('suggested'); // suggested | introduced | accepted | declined
            $table->timestamps();

            $table->index('overall_match');
        });
    }

    public function down(): void {
        Schema::dropIfExists('matches');
    }
};
