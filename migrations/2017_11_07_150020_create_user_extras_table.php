<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户基本信息表,用户主表
 * Class CreateUsersTable
 */
class CreateUserExtrasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_extras', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->unique()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');

            $table->string("register_source")->nullable()->comment("注册来源：1手机号 2邮箱 3用户名 4qq 5微信 6腾讯微博 7新浪微博");
            $table->timestamp("mobile_bind_time")->nullable()->comment("手机绑定时间");
            $table->timestamp("email_bind_time")->nullable()->comment("email绑定时间");
            $table->timestamp("username_bind_time")->nullable()->comment("用户号绑定时间");

            $table->string("client_name")->nullable()->comment("客户端名称，如hjskang");
            $table->string("client_version")->nullable()->comment("客户端版本号，如7.0.1");
            $table->string("os_name")->nullable()->comment("系统:android|ios");
            $table->string("os_version")->nullable()->comment("系统版本号:2.2|2.3|4.0|5.1");
            $table->string("device_name")->nullable()->comment("设备型号，如:iphone6s、u880、u8800");
            $table->string("device_id")->nullable()->comment("设备ID");
            $table->string("idfa")->nullable()->comment("苹果设备的IDFA");
            $table->string("idfv")->nullable()->comment("苹果设备的IDFV");
            $table->string("market")->nullable()->comment("来源");
            $table->json("extra")->nullable();

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
        Schema::drop('user_extras');
    }
}
