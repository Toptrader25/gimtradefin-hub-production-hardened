<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // "Request Introduction" from the doc's hero flow (section 1) and
        // Financing Assessment requests (section 4) both live here as
        // enquiries against a specific opportunity.
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('enquirer_name');
            $table->string('enquirer_email');
            $table->string('enquirer_company')->nullable();
            $table->enum('type', ['introduction_request', 'financing_assessment', 'general'])
                  ->default('introduction_request');
            $table->text('message')->nullable();
            $table->string('status')->default('new'); // new | in_progress | resolved
            $table->timestamps();

            $table->index('opportunity_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('enquiries');
    }
};
