<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户累计数据
 */
class CreateUserCumulateTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_cumulates', function (Blueprint $table) {
            $table->increments('id');
            $table->string("subject_id");
            $table->string("ref_date");
            $table->integer("cumulate_user")->comment("总用户量");
            $table->integer("new_user")->nullable();
            $table->string("type")
                ->default("day")
                ->comment("累计数据统计的时间范围:day/month/year");
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
        Schema::dropIfExists('user_cumulates');
    }
}
