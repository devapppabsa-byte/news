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
            $table->foreignId('news_entry_id')->nullable()->after('topic_run_id')
                ->constrained('news_entries')->nullOnDelete();

            $table->string('novedad_status', 20)->default('new')->after('status');

            $table->boolean('is_read')->default(false)->after('novedad_status');
            $table->timestamp('read_at')->nullable()->after('is_read');
            $table->boolean('is_saved')->default(false)->after('read_at');
            $table->timestamp('saved_at')->nullable()->after('is_saved');
            $table->boolean('is_irrelevant')->default(false)->after('saved_at');
            $table->timestamp('irrelevant_at')->nullable()->after('is_irrelevant');

            $table->index(['topic_id', 'is_read']);
            $table->index(['topic_id', 'is_saved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropIndex(['topic_id', 'is_read']);
            $table->dropIndex(['topic_id', 'is_saved']);
            $table->dropConstrainedForeignId('news_entry_id');
            $table->dropColumn([
                'novedad_status',
                'is_read', 'read_at',
                'is_saved', 'saved_at',
                'is_irrelevant', 'irrelevant_at',
            ]);
        });
    }
};
