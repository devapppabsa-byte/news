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
        Schema::table('topic_runs', function (Blueprint $table) {
            $table->timestamp('queued_at')->nullable()->after('finished_at');
            $table->unsignedInteger('duration_ms')->nullable()->after('queued_at');
            $table->unsignedTinyInteger('attempts')->default(0)->after('duration_ms');
            $table->decimal('estimated_cost', 10, 6)->default(0)->after('attempts');
            $table->json('provider_usage')->nullable()->after('estimated_cost');

            $table->index(['monitored_topic_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topic_runs', function (Blueprint $table) {
            $table->dropIndex(['monitored_topic_id', 'status']);
            $table->dropColumn(['queued_at', 'duration_ms', 'attempts', 'estimated_cost', 'provider_usage']);
        });
    }
};
