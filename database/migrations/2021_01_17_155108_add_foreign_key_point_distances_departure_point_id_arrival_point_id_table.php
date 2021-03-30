<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyPointDistancesDeparturePointIdArrivalPointIdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('point_distances', function (Blueprint $table) {
            $table->foreign('departure_point_id', 'fk_point_distances_departure_point_id_points_id')->references('id')->on('points')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('arrival_point_id', 'fk_point_distances_arrival_point_id_points_id')->references('id')->on('points')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('point_distances', function (Blueprint $table) {
            $table->dropForeign('fk_point_distances_departure_point_id_points_id');
            $table->dropForeign('fk_point_distances_arrival_point_id_points_id');
        });
    }
}
