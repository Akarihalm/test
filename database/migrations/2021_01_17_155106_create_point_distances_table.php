<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePointDistancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_distances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('departure_point_id')->index('point_distances_departure_point_id_idx');
            $table->unsignedBigInteger('arrival_point_id')->index('point_distances_arrival_point_id_idx');
            $table->unsignedSmallInteger('ticket');
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
        Schema::dropIfExists('point_distances');
    }
}
