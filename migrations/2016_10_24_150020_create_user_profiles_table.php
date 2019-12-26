<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户信息表
 * 包括如:会员信息/停车信息/微信信息等.这些信息不一定每一个项目都有,按需添加.
 *
 * 会员信息如对接第三方则保存第三方信息,如果是自己的则保存自己的.
 * ClassCreateUserProfilesTable
 */
class CreateUserProfilesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');

            //环信
//            $table->string('easemob_id')->nullable()->comment('环信id');
//            $table->string('easemob_username')->nullable()->comment('环信用户名');
//            $table->string('easemob_password')->nullable()->comment('环信密码');

            //停车相关信息

            //用户信息
            //1.基本信息
//            $table->date('birthday')->nullable()->comment('用户生日');
//            $table->timestamp('registed_at')->nullable()->comment('注册时间');

            //微信信息
            $table->string('wechat_nickname')->nullable()->comment('微信昵称');
            $table->string('wechat_avatar')->nullable()->comment('微信头像');
            $table->string('wechat_province')->nullable()->comment('微信省份');
            $table->string('wechat_city')->nullable()->comment('微信城市');
            $table->string('wechat_country')->nullable()->comment('微信国家');
            $table->integer('wechat_sex')->nullable()->comment('微信性别,1男;2女;0未知');
            $table->string('wechat_language')->nullable()->comment('微信用户语言');
            $table->json('wechat_privilege')->nullable()->comment('微信用户特权信息，json 数组，如微信沃卡用户为（chinaunicom');
            $table->string('wechat_unionid')->nullable()->comment('只有将公众号绑定到开放平台才会出现此字段');

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
        Schema::dropIfExists('user_profiles');
    }
}
