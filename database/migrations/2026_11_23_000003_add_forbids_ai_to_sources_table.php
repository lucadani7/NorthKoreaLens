<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            // The source forbids using AI tools on its content (e.g. Yonhap): readers are told,
            // and NK Lens never runs AI features on these posts.
            $table->boolean('forbids_ai')->default(false)->after('needs_review');
        });
    }

    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropColumn('forbids_ai');
        });
    }
};
