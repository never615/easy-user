<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户基本信息表,用户主表
 * Class CreateUsersTable
 */
class CreateUserRegisterLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_register_logs', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');

            $table->string("register_method")->nullable()->comment("注册方式：1手机号 2邮箱 3用户名 4qq 5微信 6腾讯微博 7新浪微博");
            $table->string("version")->nullable()->comment("客户端版本号");
            $table->string("client")->nullable()->comment("客户端");
            $table->string("device_id")->nullable()->comment("设备id");
            $table->ipAddress("ip")->nullable()->comment("ip地址");
            $table->string("os")->nullable()->comment("系统");
            $table->string("os_version")->nullable()->comment("系统版本");
            $table->text("remark")->nullable()->comment("备注");

            $table->timestamps();
            $table->softDeletes();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_register_logs');
    }
}
