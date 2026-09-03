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
        if (! Schema::hasColumn('grades', 'number')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->unsignedInteger('number')->nullable()->unique()->after('name');
            });
        }

        if (DB::table('admins')->where('id', 1)->exists()) {
            $now = now();
            $grades = [];

            for ($i = 1; $i <= 10; $i++) {
                $grades[] = [
                    'name' => "Year {$i}",
                    'number' => $i,
                    'level' => '',
                    'admin_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('grades')->insert($grades);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            if (Schema::hasColumn('grades', 'number')) {
                $table->dropColumn('number');
            }
        });
    }
};
