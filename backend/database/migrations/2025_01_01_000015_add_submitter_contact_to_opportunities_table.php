<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Public submissions happen before auth exists (auth is Phase 3).
        // submitted_by_user_id stays null for these — these columns are
        // how we still know who to follow up with.
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('submitted_by_name')->nullable()->after('submitted_by_user_id');
            $table->string('submitted_by_email')->nullable()->after('submitted_by_name');
            $table->string('submitted_by_company')->nullable()->after('submitted_by_email');
            $table->string('submitted_by_phone')->nullable()->after('submitted_by_company');
        });
    }

    public function down(): void {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn([
                'submitted_by_name', 'submitted_by_email', 'submitted_by_company', 'submitted_by_phone',
            ]);
        });
    }
};
