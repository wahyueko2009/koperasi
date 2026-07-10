<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_usaha_inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('unit_usaha_inventories', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('name')->constrained('unit_usaha_inventory_categories')->nullOnDelete();
        });

        $categories = DB::table('unit_usaha_inventories')
            ->select('category')
            ->whereNotNull('category')
            ->get()
            ->pluck('category')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        $mappedIds = [];
        foreach ($categories as $index => $name) {
            $id = DB::table('unit_usaha_inventory_categories')->insertGetId([
                'code' => sprintf('JBR-%03d', $index + 1),
                'name' => Str::title(Str::lower($name)),
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $mappedIds[$name] = $id;
        }

        DB::table('unit_usaha_inventories')
            ->select('id', 'category')
            ->orderBy('id')
            ->get()
            ->each(function ($inventory) use ($mappedIds): void {
                $name = trim((string) $inventory->category);
                if ($name === '' || ! isset($mappedIds[$name])) {
                    return;
                }

                DB::table('unit_usaha_inventories')
                    ->where('id', $inventory->id)
                    ->update(['category_id' => $mappedIds[$name]]);
            });
    }

    public function down(): void
    {
        Schema::table('unit_usaha_inventories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('unit_usaha_inventory_categories');
    }
};
