<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeersIpTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('peers_ip', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('peerID')->unsigned()->index();
            $table->foreign('peerID')->references('id')->on('peers')->onUpdate('cascade')->onDelete('cascade');
            $table->string('IP', 39);
            $table->unsignedSmallInteger('port');
            $table->boolean('isIPv6')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('peers_ip');
    }
}
