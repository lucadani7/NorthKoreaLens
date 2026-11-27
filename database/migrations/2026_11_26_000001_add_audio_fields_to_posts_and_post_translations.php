<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_nklens_original')->default(false);
            $table->string('audio_path')->nullable();
            $table->char('audio_hash', 64)->nullable();
            $table->timestamp('audio_generated_at')->nullable();
        });

        DB::table('posts')
            ->whereNull('source_id')
            ->where(function ($query) {
                $query->where('guid', 'like', 'nklens:%')
                    ->orWhere(function ($query) {
                        $query->whereNull('guid')
                            ->whereNull('original_url');
                    });
            })
            ->update(['is_nklens_original' => true]);

        Schema::table('post_translations', function (Blueprint $table) {
            $table->string('audio_path')->nullable();
            $table->char('audio_hash', 64)->nullable();
            $table->timestamp('audio_generated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('post_translations', function (Blueprint $table) {
            $table->dropColumn(['audio_path', 'audio_hash', 'audio_generated_at']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['is_nklens_original', 'audio_path', 'audio_hash', 'audio_generated_at']);
        });
    }
};
