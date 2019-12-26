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
class CreateUserInfoUpdateLogsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_info_update_logs', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');

            $table->string("atr_name")->nullable()->comment("属性名");
            $table->string("atr_old_val")->nullable()->comment("旧的属性值");
            $table->string("atr_new_val")->nullable()->comment("新的属性值");

            $table->json("update_info")->nullable()->comment("更新内容,使用这个的话,就不适用上面的那三项");

            $table->text("remark")->nullable();

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
        Schema::drop('user_info_update_logs');
    }
}
