<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index('reservations_user_id_idx');
            $table->enum('status', App\Enums\ReservationAnswerStatusEnum::values())->index('reservations_status_idx')->nullable();
            $table->string('name', 128);
            $table->string('tel', 16);
            $table->unsignedBigInteger('departure_point_id')->index('reservations_departure_point_id_idx');
            $table->unsignedBigInteger('arrival_point_id')->index('reservations_arrival_point_id_idx');
            $table->string('picture', 256)->nullable();
            $table->string('passengers', 256)->nullable();
            $table->datetime('pick_up_scheduled_time');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reservations');
    }
}
