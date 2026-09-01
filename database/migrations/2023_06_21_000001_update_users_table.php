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
            // Drop existing columns
            $table->dropColumn('name');
            
            // Add new columns
            $table->uuid('id')->change();
            $table->string('username')->after('id');
            $table->string('full_name')->nullable()->after('username');
            $table->enum('role', ['admin', 'customer'])->default('customer')->after('password');
            $table->string('address')->nullable()->after('role');
            $table->string('city')->nullable()->after('address');
            $table->string('contact')->nullable()->after('city');
            $table->string('avatar')->nullable()->after('contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert changes
            $table->dropColumn(['username', 'full_name', 'role', 'address', 'city', 'contact', 'avatar']);
            $table->string('name')->after('id');
            $table->bigIncrements('id')->change();
        });
    }
};
