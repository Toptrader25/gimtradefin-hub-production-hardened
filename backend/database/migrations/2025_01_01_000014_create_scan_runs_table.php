<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('scan_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('status')->default('running'); // running | success | error
            $table->unsignedInteger('records_found')->default(0);
            $table->unsignedInteger('records_new')->default(0);
            $table->unsignedInteger('records_duplicate')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('source_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('scan_runs');
    }
};
