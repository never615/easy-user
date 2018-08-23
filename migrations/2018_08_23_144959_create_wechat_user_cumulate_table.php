<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 微信用户累计数据
 * 获取自微信接口
 * Class CreateWechatUserStatistics
 */
class CreateWechatUserCumulateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_user_cumulates', function (Blueprint $table) {
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
        Schema::dropIfExists('wechat_user_cumulates');
    }
}
