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
        Schema::create('status', function (Blueprint $table) {
            $table->id();
            $table->string('current_status',50);
            $table->timestamps();
        });

        Schema::create('food_stores', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable(); // Optional remarks
            $table->string('company', 255);
            $table->string('store_hours', 100);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('name', 50); // Name of the menu item
            $table->string('description', 255); // Description of the item
            $table->integer('price'); // Price of the item
            $table->foreignId('food_store_id') // Foreign key referencing food_stores table
                  ->constrained('food_stores','id') // Table name being referenced
                  ->onDelete('cascade'); // Cascade delete if parent is deleted
            $table->timestamps(); // Timestamps for created_at and updated_at
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->foreignId('user_id') // Foreign key referencing the users table
                ->constrained('users', 'id') 
                ->onDelete('cascade'); // Cascade delete if user is deleted

            $table->foreignId('status_id') // Foreign key referencing the statuses table
                ->constrained('status', 'id') 
                ->onDelete('cascade'); 

            $table->text('remarks')->nullable(); // Optional remarks
            $table->string('delivery_address', 255); // Delivery address
            $table->string('payment_method', 255); // Payment method
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
        Schema::create('user_transactions', function (Blueprint $table) {
            $table->id(); // Custom primary key
            $table->foreignId('menu_id')
                  ->constrained('menu_items', 'id') // Reference 'book_id' in 'books'
                  ->onDelete('cascade'); // Handle cascading deletes
            $table->foreignId('order_id')
                  ->constrained('orders', 'id') // Reference 'book_id' in 'books'
                  ->onDelete('cascade'); // Handle cascading deletes
            $table->integer('price'); // Price of the item
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status');
        Schema::dropIfExists('food_stores');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('user_transactions');



    }
};
