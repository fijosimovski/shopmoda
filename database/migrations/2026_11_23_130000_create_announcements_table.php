<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Short news shown in a slim bar under the navigation (slideshow when there are more):
        // a new drop, a restock or a promotion, with a countdown to ends_at.
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('type');                   // drop / restock / promo
            $table->string('title');
            $table->string('body')->nullable();       // one short line next to the title
            $table->string('image')->nullable();      // small thumbnail in the bar
            // Where "Shop now" goes. Polymorphic: a Product, a ProductModel or a Promotion (or nothing).
            $table->nullableMorphs('linkable');
            $table->string('show_on')->default('all'); // home = only the homepage, all = every shop page
            $table->dateTime('starts_at');            // shown from
            $table->dateTime('ends_at');              // shown until; the countdown runs to this time
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
