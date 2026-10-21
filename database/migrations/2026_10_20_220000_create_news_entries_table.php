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
        Schema::create('news_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url');
            $table->string('normalized_url');
            $table->string('url_hash', 64)->unique();
            $table->string('content_hash', 64)->nullable();
            $table->string('source_name')->nullable();
            $table->string('source_domain')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('discovered_at')->nullable();
            $table->text('summary')->nullable();
            $table->unsignedTinyInteger('relevance_score')->nullable();
            $table->boolean('source_available')->nullable();
            $table->timestamp('source_checked_at')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamps();

            $table->index('content_hash');
            $table->index('status');
            $table->index('discovered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_entries');
    }
};
