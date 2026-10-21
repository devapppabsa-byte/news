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
            $table->string('url')->nullable()->after('body');
            $table->string('source_domain')->nullable()->after('url');
            $table->unsignedTinyInteger('relevance')->default(0)->after('source_domain');
            $table->foreignId('topic_run_id')->nullable()->after('topic_id')
                ->constrained('topic_runs')->nullOnDelete();

            $table->index(['topic_id', 'relevance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropForeign(['topic_run_id']);
            $table->dropIndex(['topic_id', 'relevance']);
            $table->dropColumn(['url', 'source_domain', 'relevance', 'topic_run_id']);
        });
    }
};
