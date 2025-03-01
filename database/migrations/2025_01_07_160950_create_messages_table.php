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
    Schema::create('messages', function (Blueprint $table) {
      $table->id();
      $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
      $table->text('content');
      $table->json('images')->nullable();
      $table->boolean('has_images')->default(false);
      $table->string('role'); // 'user' ou 'assistant'
      $table->string('model');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('messages');
  }
};
