<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('passkey')->unique();
            $table->string('timezone', 30);
            $table->integer('locale_id')->unsigned()->index();
            $table->foreign('locale_id')->references('id')->on('locales')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('uploaded')->default(0);
            $table->unsignedBigInteger('downloaded')->default(0);
            $table->integer('torrents_per_page')->unsigned()->default(20);
            $table->integer('invites_amount')->unsigned()->default(0);
            $table->boolean('banned')->default(false);
            $table->rememberToken();
            $table->string('slug')->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->integer('inviter_user_id')->unsigned()->nullable()->index();
            $table->foreign('inviter_user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('is_two_factor_enabled')->default(false);
            $table->string('two_factor_secret_key')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
