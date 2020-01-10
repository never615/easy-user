<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户登录记录表
 * Class CreateUserLoginsTable
 */
class CreateUserLoginsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_logins', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('subject_id');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('CASCADE');

            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');

            $table->timestamp('login_time')->comment('登录时间');
            $table->ipAddress('login_ip')->nullable()->comment('登录ip');
            $table->macAddress('mac')->nullable()->comment('登录mac');
            $table->string('login_type')->nullable()->comment('登录类型.mobile:手机;pad:平板;computer:电脑');
            $table->string('login_account_type')->nullable()->comment('登录账号类型.weixin,mobile,email,username');
            $table->timestamps();
            $table->softDeletes();

            $table->index([ 'subject_id' ]);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_logins');
    }
}
