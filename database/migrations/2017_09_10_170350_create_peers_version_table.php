<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePeersVersionTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('peers_version', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('version');
            $table->integer('peerID')->unsigned()->index();
            $table->foreign('peerID')->references('id')->on('peers')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('peers_version');
    }
}
