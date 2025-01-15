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
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            // Drop the existing primary key on the `email` column
            $table->dropPrimary('PRIMARY');

            // Add the `id` column and set it as the new primary key
            $table->id()->first();
            
            // Add back the `email` column as a normal column (if necessary, add unique constraint)
            $table->string('email')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            // Drop the `id` column
            $table->dropColumn('id');

            // Set `email` as the primary key again
            $table->primary('email');
        });
    }
};
