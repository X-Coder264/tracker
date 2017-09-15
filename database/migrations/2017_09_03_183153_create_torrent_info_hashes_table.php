<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTorrentInfoHashesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('torrent_info_hashes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('info_hash', 40)->unique();
            $table->integer('torrent_id')->unsigned()->index();
            $table->foreign('torrent_id')->references('id')->on('torrents')->onUpdate('cascade')->onDelete('cascade');
            $table->tinyInteger('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('torrent_info_hashes');
    }
}
