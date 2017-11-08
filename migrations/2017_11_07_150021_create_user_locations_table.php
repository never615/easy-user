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
class CreateUserLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_locations', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');

            $table->string("curr_nation")->nullable()->comment("所在地国");
            $table->string("curr_province")->nullable()->comment("所在地省");
            $table->string("curr_city")->nullable()->comment("所在地城市");
            $table->string("curr_district")->nullable()->comment("所在地地区");
            $table->string("location")->nullable()->comment("具体地址");
            $table->double("longitude")->nullable()->comment("经度");
            $table->double("latitude")->nullable()->comment("纬度");

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
        Schema::drop('user_locations');
    }
}
