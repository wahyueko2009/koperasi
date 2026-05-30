<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('posted_at')->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('gl_periods', function (Blueprint $table) {
            $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('posted_by');
        });

        Schema::table('gl_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by');
        });
    }
};
