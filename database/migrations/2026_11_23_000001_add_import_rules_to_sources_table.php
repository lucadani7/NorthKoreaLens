<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            // Section the posts land in; null means the default for the source type.
            $table->foreignId('category_id')->nullable()->after('type')->constrained()->nullOnDelete();
            // Only items whose title or description mentions one of these are imported; null imports all.
            $table->json('keywords')->nullable()->after('feed_url');
            // Imported items wait as drafts until someone publishes them in the admin panel.
            $table->boolean('needs_review')->default(false)->after('keywords');
        });
    }

    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['keywords', 'needs_review']);
        });
    }
};
