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
class UpdateUserProfiles3Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->json("wechat_user")->nullable()->comment("用户的微信信息");
            $table->date("birthday")->nullable()->comment("生日");
            $table->string("gender")->default("0")->comment("性别:1男2女0未知")->nullable();
            $table->text("signature")->nullable()->comment("用户的个性签名");

        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn("wechat_user");
            $table->dropColumn("birthday");
            $table->dropColumn("gender");
            $table->dropColumn("signature");
        });
    }
}
