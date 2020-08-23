<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreatePeersVersionTable extends Migration
{
    public function up(): void
    {
        Schema::create('peers_version', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('version');
            $table->integer('peer_id')->unsigned()->index();
            $table->foreign('peer_id')->references('id')->on('peers')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peers_version');
    }
}
