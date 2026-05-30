<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('members', 'login')) {
            Schema::table('members', function (Blueprint $table) {
                $table->string('login')->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('officials', 'login')) {
            Schema::table('officials', function (Blueprint $table) {
                $table->string('login')->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'login')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('login')->nullable()->after('name');
            });
        }

        DB::table('users')
            ->whereNull('login')
            ->update(['login' => DB::raw('email')]);

        $memberLogins = DB::table('users')
            ->whereNotNull('member_id')
            ->whereNotNull('login')
            ->pluck('login', 'member_id');

        foreach ($memberLogins as $memberId => $login) {
            DB::table('members')
                ->where('id', $memberId)
                ->whereNull('login')
                ->update(['login' => $login]);
        }

        $officialLogins = DB::table('users')
            ->whereNotNull('official_id')
            ->whereNotNull('login')
            ->pluck('login', 'official_id');

        foreach ($officialLogins as $officialId => $login) {
            DB::table('officials')
                ->where('id', $officialId)
                ->whereNull('login')
                ->update(['login' => $login]);
        }

        Schema::table('members', function (Blueprint $table) {
            $table->unique('login');
        });

        Schema::table('officials', function (Blueprint $table) {
            $table->unique('login');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('login');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login']);
            $table->dropColumn('login');
        });

        Schema::table('officials', function (Blueprint $table) {
            $table->dropUnique(['login']);
            $table->dropColumn('login');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['login']);
            $table->dropColumn('login');
        });
    }
};
