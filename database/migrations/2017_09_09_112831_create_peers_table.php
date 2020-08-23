<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreatePeersTable extends Migration
{
    public function up(): void
    {
        Schema::create('peers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('peer_id', 40);
            $table->string('key')->nullable();
            $table->integer('torrent_id')->unsigned()->index();
            $table->foreign('torrent_id')->references('id')->on('torrents')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('uploaded')->default(0);
            $table->unsignedBigInteger('downloaded')->default(0);
            $table->unsignedBigInteger('left');
            $table->string('user_agent');
            $table->index(['peer_id', 'key', 'torrent_id']);
            $table->index(['user_id', 'torrent_id', 'left']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peers');
    }
}
