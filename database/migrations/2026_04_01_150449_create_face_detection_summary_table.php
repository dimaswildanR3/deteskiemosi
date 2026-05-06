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
        Schema::create('face_detection_summary', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('class_id')->nullable();
            $table->integer('total_people')->default(0);
            $table->integer('total_smile')->default(0);
            $table->integer('total_neutral')->default(0);
            $table->integer('total_other')->default(0);
            $table->integer('total_images')->default(0);
            $table->timestamp('last_detection')->nullable();
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_detection_summary');
    }
};
