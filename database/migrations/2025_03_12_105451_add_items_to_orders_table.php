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
        Schema::table('orders', function (Blueprint $table) {
            // Only add the items column since product_id, qty, and size are already gone
            $table->json('items')->after('order_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Reverse: drop the items column
            $table->dropColumn('items');
            
            // Recreate the original columns
            $table->unsignedBigInteger('product_id');
            $table->integer('qty');
            $table->string('size');
            
            // Recreate the foreign key constraint
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
        });
    }
};
