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
        Schema::create('posts', function (Blueprint $table) {
            $table->id('id');
            $table->string('title');
            $table->text('content');
            $table->string('slug')->comment("better name in the address bar");
            // Optional parts of the posts
            $table->timestamp('publication_date')->nullable();
            $table->timestamp('last_modified_date')->nullable();
            $table->string('status')->max(1)->comment('D - Draft, P - Published, I - Inactive)');
            $table->text('featured_image_url')->nullable();
            $table->integer('views_count')->default(0)->comment("the number of times the post has been viewed.");
            // Timestamps (created_at & updated_at)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
