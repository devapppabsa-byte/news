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
            $table->text('ai_summary')->nullable()->after('body');
            $table->unsignedTinyInteger('ai_relevance')->nullable()->after('ai_summary');
            $table->foreignId('ai_duplicate_of_id')->nullable()->after('ai_relevance')
                ->constrained('news_items')->nullOnDelete();
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_duplicate_of_id');

            $table->index('ai_relevance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropIndex(['ai_relevance']);
            $table->dropConstrainedForeignId('ai_duplicate_of_id');
            $table->dropColumn(['ai_summary', 'ai_relevance', 'ai_analyzed_at']);
        });
    }
};
