<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();

            $table->string('signal_type');   // e.g. "expansion_news", "investment_announcement", "distributor_search"
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('strength')->nullable(); // 0-100
            $table->timestamp('detected_at');
            $table->timestamps();

            $table->index('signal_type');
        });
    }

    public function down(): void {
        Schema::dropIfExists('signals');
    }
};
