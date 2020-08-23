<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreatePeersIpTable extends Migration
{
    public function up(): void
    {
        Schema::create('peers_ip', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('peer_id')->unsigned()->index();
            $table->foreign('peer_id')->references('id')->on('peers')->onUpdate('cascade')->onDelete('cascade');
            $table->string('ip', 39);
            $table->boolean('is_ipv6')->default(false);
            $table->unsignedSmallInteger('port');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peers_ip');
    }
}
