<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTorrentsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('torrents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->unsignedBigInteger('size')->default(0);
            $table->integer('uploader_id')->unsigned()->index();
            $table->foreign('uploader_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('category_id')->unsigned()->index();
            $table->foreign('category_id')->references('id')->on('torrent_categories')->onUpdate('cascade')->onDelete('cascade');
            $table->text('description');
            $table->smallInteger('seeders')->unsigned()->default(0)->index();
            $table->smallInteger('leechers')->unsigned()->default(0);
            $table->integer('views_count')->unsigned()->default(0);
            $table->string('imdb_id')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('torrents');
    }
}
