<?php

use App\Enums\SearchFrequency;
use App\Enums\TopicStatus;
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
        Schema::table('topics', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });

        Schema::rename('topics', 'monitored_topics');

        Schema::table('monitored_topics', function (Blueprint $table) {
            $table->string('language', 5)->default('es')->after('description');
            $table->string('country', 2)->nullable()->after('language');
            $table->string('frequency', 20)->default(SearchFrequency::Daily->value)->after('country');
            $table->unsignedInteger('custom_interval_minutes')->nullable()->after('frequency');
            $table->string('status', 20)->default(TopicStatus::Active->value)->after('custom_interval_minutes');
            $table->timestamp('last_run_at')->nullable()->after('status');
            $table->timestamp('next_run_at')->nullable()->after('last_run_at');
            $table->boolean('notify_email')->default(true)->after('next_run_at');
            $table->boolean('notify_push')->default(false)->after('notify_email');
            $table->json('relevance_preferences')->nullable()->after('notify_push');

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'next_run_at']);
        });

        Schema::table('news_items', function (Blueprint $table) {
            $table->dropForeign(['topic_id']);
            $table->foreign('topic_id')
                ->references('id')
                ->on('monitored_topics')
                ->nullOnDelete();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['topic_id']);
            $table->foreign('topic_id')
                ->references('id')
                ->on('monitored_topics')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['topic_id']);
            $table->foreign('topic_id')->references('id')->on('topics')->cascadeOnDelete();
        });

        Schema::table('news_items', function (Blueprint $table) {
            $table->dropForeign(['topic_id']);
            $table->foreign('topic_id')->references('id')->on('topics')->nullOnDelete();
        });

        Schema::table('monitored_topics', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['status', 'next_run_at']);
            $table->dropColumn([
                'language',
                'country',
                'frequency',
                'custom_interval_minutes',
                'status',
                'last_run_at',
                'next_run_at',
                'notify_email',
                'notify_push',
                'relevance_preferences',
            ]);
        });

        Schema::table('monitored_topics', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::rename('monitored_topics', 'topics');
    }
};
