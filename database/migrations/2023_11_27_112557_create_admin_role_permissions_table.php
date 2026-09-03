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
        Schema::create('admin_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->unsigned()->constrained('admin_roles')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('permission_id')->unsigned()->constrained('admin_permissions')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('view');
            $table->boolean('add');
            $table->boolean('edit');
            $table->boolean('delete');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_role_permissions');
    }
};
