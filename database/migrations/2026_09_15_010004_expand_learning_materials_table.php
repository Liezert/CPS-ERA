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
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->integer('xp_reward')->nullable()->after('description');
            $table->foreignUuid('source_ba_id')->nullable()->after('xp_reward')->constrained('ba_incidents')->nullOnDelete();
            if (! Schema::hasColumn('learning_materials', 'status')) {
                $table->string('status')->default('published')->after('source_ba_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->dropForeign(['source_ba_id']);
            $table->dropColumn(['source_ba_id', 'xp_reward']);
        });
    }
};
