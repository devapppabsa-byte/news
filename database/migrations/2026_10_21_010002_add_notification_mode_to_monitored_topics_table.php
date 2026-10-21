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
        Schema::table('monitored_topics', function (Blueprint $table) {
            $table->string('notification_mode', 20)->nullable()->after('notify_push');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitored_topics', function (Blueprint $table) {
            $table->dropColumn('notification_mode');
        });
    }
};
