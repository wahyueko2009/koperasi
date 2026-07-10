<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_usaha_inventory_categories', function (Blueprint $table) {
            $table->string('usage_type', 20)->default('barang')->after('name');
        });

        DB::table('unit_usaha_inventory_categories')
            ->orderBy('id')
            ->get()
            ->each(function ($category): void {
                $label = strtolower(trim(($category->code ?? '') . ' ' . ($category->name ?? '')));
                $usageType = str_contains($label, 'photo')
                    || str_contains($label, 'jasa')
                    || str_contains($label, 'service')
                    ? 'jasa'
                    : 'barang';

                DB::table('unit_usaha_inventory_categories')
                    ->where('id', $category->id)
                    ->update([
                        'usage_type' => $usageType,
                        'name' => $category->code,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('unit_usaha_inventory_categories', function (Blueprint $table) {
            $table->dropColumn('usage_type');
        });
    }
};
