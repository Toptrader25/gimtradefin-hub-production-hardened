<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();

            $table->string('original_url')->nullable();
            $table->string('source_record_id')->nullable();
            $table->timestamp('discovered_at');
            $table->timestamp('last_checked_at')->nullable();
            $table->text('excerpt')->nullable();           // the actual text snippet that justified the record
            $table->string('evidence_type')->nullable();   // e.g. "listing", "announcement", "rfq"
            $table->timestamp('evidence_timestamp')->nullable(); // when the evidence itself was published/dated
            $table->unsignedTinyInteger('source_confidence')->nullable(); // 0-100
            $table->timestamps();

            $table->index('opportunity_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('evidence');
    }
};
