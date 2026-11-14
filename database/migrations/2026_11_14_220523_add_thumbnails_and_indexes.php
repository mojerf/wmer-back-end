<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private const CONTENT_TABLES = ['works', 'posts', 'products'];

    public function up(): void
    {
        foreach (self::CONTENT_TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                // Small WebP version of the cover, used by list pages.
                $table->string('image_thumb')->nullable()->after('image');
                // Every public page looks content up by slug: it needs an index (and must be unique).
                $table->unique('slug');
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['created_at']);
        });

        foreach (self::CONTENT_TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropUnique(['slug']);
                $table->dropColumn('image_thumb');
            });
        }
    }
};
