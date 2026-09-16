<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('source_type')->nullable();     // e.g. "B2B sourcing platform", "trade association"
            $table->enum('connector_type', [
                'api', 'rss', 'xml', 'csv', 'public_html', 'partner_feed', 'licensed_data', 'manual_import',
            ]);
            $table->string('access_method')->nullable();
            $table->unsignedTinyInteger('reliability_score')->nullable();          // 0-100
            $table->unsignedTinyInteger('commercial_relevance_score')->nullable(); // 0-100

            // Source hierarchy — section 10. This directly affects scoring
            // weight: tier 1 (direct buyer/seller platforms) carries more
            // weight than tier 3 (government procurement).
            $table->unsignedTinyInteger('tier')->default(3); // 1 = primary commercial, 2 = intelligence, 3 = supporting

            $table->string('url')->nullable();
            $table->text('access_notes')->nullable();       // terms/access restrictions
            $table->timestamp('last_scan_at')->nullable();
            $table->string('scan_status')->default('pending'); // pending | ok | error | disabled
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('connector_type');
            $table->index('tier');
        });
    }

    public function down(): void {
        Schema::dropIfExists('sources');
    }
};
