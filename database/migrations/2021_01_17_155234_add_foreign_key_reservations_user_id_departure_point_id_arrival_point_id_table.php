<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyReservationsUserIdDeparturePointIdArrivalPointIdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreign('user_id', 'fk_reservations_user_id_users_id')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('departure_point_id', 'fk_reservations_departure_point_id_points_id')->references('id')->on('points')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('arrival_point_id', 'fk_reservations_arrival_point_id_points_id')->references('id')->on('points')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign('fk_reservations_user_id_users_id');
            $table->dropForeign('fk_reservations_departure_point_id_points_id');
            $table->dropForeign('fk_reservations_arrival_point_id_points_id');
        });
    }
}
