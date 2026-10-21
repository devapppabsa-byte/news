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
        Schema::table('news_items', function (Blueprint $table) {
            $table->string('author')->nullable()->after('source_domain');
            $table->timestamp('collected_at')->nullable()->after('relevance');
            $table->string('source_identifier')->nullable()->after('collected_at');

            $table->index('source_identifier');
            $table->index(['topic_id', 'url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropIndex(['source_identifier']);
            $table->dropIndex(['topic_id', 'url']);
            $table->dropColumn(['author', 'collected_at', 'source_identifier']);
        });
    }
};
