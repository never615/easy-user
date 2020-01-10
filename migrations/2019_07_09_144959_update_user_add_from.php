<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 添加注册来源
 * Class UpdateUserAddFrom
 */
class UpdateUserAddFrom extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string("from")
                ->default("wechat");

            $table->string("from_third_app_id")
                ->nullable()
                ->comment("第三方系统注册时的app id");
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('from');
            $table->dropColumn('from_third_app_id');
        });
    }
}
