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
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('number')->unique();
            $table->foreignId('admin_id')->unsigned()->constrained('admins')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });

        $permissionId = DB::table('admin_permissions')->insertGetId([
            'key' => 'levels',
            'name' => 'Levels',
            'view' => 1,
            'add' => 1,
            'edit' => 1,
            'delete' => 1,
        ]);

        if (Schema::hasTable('admin_roles') && DB::table('admin_roles')->exists()) {
            $roleIds = DB::table('admin_roles')->where('slug', 'super-admin')->pluck('id');

            foreach ($roleIds as $roleId) {
                DB::table('admin_role_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'view' => 1,
                    'add' => 1,
                    'edit' => 1,
                    'delete' => 1,
                ]);
            }
        }
        if (DB::table('admins')->where('id', 1)->exists()) {
            $now = now();
            $levels = [];

            for ($i = 1; $i <= 10; $i++) {
                $levels[] = [
                    'number' => $i,
                    'name' => "Level {$i}",
                    'admin_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('levels')->insert($levels);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionId = DB::table('admin_permissions')->where('key', 'levels')->value('id');

        if ($permissionId) {
            DB::table('admin_role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('admin_permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('levels');
    }
};
