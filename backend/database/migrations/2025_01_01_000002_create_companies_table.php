<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country');
            $table->string('industry')->nullable();
            $table->string('role')->nullable();          // e.g. "Importer / Distributor"
            $table->text('description')->nullable();
            $table->json('markets')->nullable();          // e.g. ["Vietnam","Cambodia","Thailand"]
            $table->json('products')->nullable();         // e.g. ["Rice","Grains"]
            $table->string('website')->nullable();
            $table->string('verification_status')        // unknown | pending | verified
                  ->default('unknown');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('country');
            $table->index('verification_status');
        });
    }

    public function down(): void {
        Schema::dropIfExists('companies');
    }
};
