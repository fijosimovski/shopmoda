<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // A model can't be deleted while it still has products.
            $table->foreignId('model_id')->nullable()->after('brand_id')->constrained('models')->restrictOnDelete();
        });

        // Products that already exist get their own model (same name as the product),
        // so nothing is left without a parent. They can be regrouped in the admin panel.
        foreach (DB::table('products')->get() as $product) {
            $modelId = DB::table('models')->insertGetId([
                'brand_id' => $product->brand_id,
                'name' => $product->name,
                'slug' => $product->slug,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('products')->where('id', $product->id)->update(['model_id' => $modelId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('model_id');
        });
    }
};
