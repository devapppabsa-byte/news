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
        Schema::create('topic_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitored_topic_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('value');
            $table->timestamps();

            $table->unique(['monitored_topic_id', 'type', 'value'], 'topic_keywords_unique');
            $table->index(['monitored_topic_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_keywords');
    }
};
