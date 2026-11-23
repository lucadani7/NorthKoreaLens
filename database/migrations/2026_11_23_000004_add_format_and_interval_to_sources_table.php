<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            // How feed_url is read: "feed" (RSS or Atom) or "rodong" (the Rodong Sinmun home page).
            $table->string('feed_format', 20)->default('feed')->after('feed_url');
            // Hours between two fetches; slow sites such as Rodong Sinmun are read once a day.
            $table->unsignedSmallInteger('fetch_every_hours')->default(1)->after('feed_format');
        });
    }

    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropColumn(['feed_format', 'fetch_every_hours']);
        });
    }
};
