<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSnatchesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('snatches', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('torrent_id')->unsigned()->index();
            $table->foreign('torrent_id')->references('id')->on('torrents')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->unique(['torrent_id', 'user_id']);
            $table->unsignedBigInteger('uploaded')->default(0);
            $table->unsignedBigInteger('downloaded')->default(0);
            $table->unsignedBigInteger('left')->index();
            $table->integer('seedTime')->unsigned()->default(0);
            $table->integer('leechTime')->unsigned()->default(0);
            $table->integer('timesAnnounced')->unsigned()->default(0);
            $table->timestamp('finished_at')->nullable();
            $table->string('userAgent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('snatches');
    }
}
