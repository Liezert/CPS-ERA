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
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id', 20)->nullable()->unique()->after('id');
            $table->foreignId('division_id')->nullable()->after('employee_id')->constrained('divisions')->nullOnDelete();
            $table->string('jabatan', 100)->nullable()->after('division_id');
            $table->string('avatar_url', 255)->nullable()->after('jabatan');
            $table->integer('total_points')->default(0)->after('avatar_url');
            $table->integer('level')->default(1)->after('total_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn([
                'employee_id',
                'division_id',
                'jabatan',
                'avatar_url',
                'total_points',
                'level',
            ]);
        });
    }
};
