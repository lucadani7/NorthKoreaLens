<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // A post comes either from a source's feed, or is written by a user (team or community).
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            $table->string('title');
            $table->text('excerpt')->nullable(); // short excerpt only for third-party articles
            $table->longText('body')->nullable(); // full text only for our own content
            $table->string('original_url', 2048)->nullable();
            $table->string('guid')->nullable(); // RSS item id, used to avoid duplicates
            $table->string('image_url', 2048)->nullable();
            $table->string('language', 5)->default('en');
            $table->timestamp('published_at')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['source_id', 'guid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
