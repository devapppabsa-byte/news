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
        Schema::create('topic_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitored_topic_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('domain');
            $table->timestamps();

            $table->unique(['monitored_topic_id', 'type', 'domain'], 'topic_sources_unique');
            $table->index(['monitored_topic_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_sources');
    }
};
