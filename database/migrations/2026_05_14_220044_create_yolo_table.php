<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('yolo', function (Blueprint $table) {

        $table->id();

        $table->unsignedBigInteger('user_id')->nullable();

        $table->unsignedBigInteger('class_id')->nullable();

        $table->string('session_name')->nullable();

        // $table->integer('duration_minutes')->default(5); 

        $table->integer('total_captures')->default(0);

        $table->decimal('positive_rate', 5, 2)->default(0);

        $table->decimal('avg_sentiment', 5, 2)->default(0);

        $table->timestamp('started_at')->nullable();

        $table->timestamp('ended_at')->nullable();

        $table->timestamps();

        $table->foreign('class_id')
            ->references('id')
            ->on('classes')
            ->onDelete('cascade');

    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yolo');
    }
};
