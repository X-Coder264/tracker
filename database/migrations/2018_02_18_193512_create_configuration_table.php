<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfigurationTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('configuration', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('configuration');
    }
}
