<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAliUserInfosTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ali_user_infos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ali_user_id');
            $table->string('nickname')->nullable()->comment('昵称');
            $table->string('sex')->nullable()->comment('性别');
            $table->string('language')->nullable()->comment('语言');
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();
            $table->string('avatar')->nullable()->comment('头像');
            $table->string('app_id')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ali_user_infos');
    }
}
