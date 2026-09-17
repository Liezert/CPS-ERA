<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->string('ledger_type')->nullable()->after('user_id');
        });

        // Backfill data lama sebagai 'xp'
        DB::table('point_transactions')->update(['ledger_type' => 'xp']);

        // Set NOT NULL tanpa default
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->string('ledger_type')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropColumn('ledger_type');
        });
    }
};
