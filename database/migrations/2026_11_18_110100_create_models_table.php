<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * A model is the parent of products, e.g. "Air Jordan 1" is the model and
     * "Air Jordan 1 Chicago" / "Air Jordan 1 Bred" are products (colorways) of it.
     */
    public function up(): void
    {
        Schema::create('models', function (Blueprint $table) {
            $table->id();
            // A brand can't be deleted while it still has models.
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique(); // used in URLs, e.g. /products?model=air-jordan-1
            $table->string('image')->nullable(); // path in storage/app/public/models, or a full URL
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('models');
    }
};
