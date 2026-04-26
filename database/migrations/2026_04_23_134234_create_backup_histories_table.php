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
        Schema::create('backup_histories', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->string('file_size')->nullable(); // dalam bytes, tapi bisa nullable jika gagal
            $table->enum('status', ['success', 'failed']);
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // developer yang melakukan backup
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_histories');
    }
};
