<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户基本信息表,用户主表
 * Class CreateUsersTable
 */
class CreateUsersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');

            //一些常用的用户基本信息放在这里,可根据项目需求调整

            //像手机号这种技术于用户展示信息,又属于用于鉴权信息的,则需要在授权表和用户表都存一遍.

            $table->integer('subject_id')->comment('主体id');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('CASCADE');

            //用户信息
            //1.基本信息
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('username')->nullable();
            $table->string('avatar')->nullable()->comment('用户头像');
            $table->string('nickname')->nullable()->comment('用户昵称');
//            $table->string('real_name')->nullable()->comment('用户真实姓名');
//            $table->integer('sex')->unsigned()->default(0)->comment('用户性别:1男;2女;0未知');
//            $table->date('birthday')->nullable()->comment('用户生日');

            //2.会员系统相关
//            $table->string('card_no')->nullable()->comment('会员卡号');
//            $table->string('card_type')->default('10')->comment('会员类型/级别.10:微卡,01:银卡;02:金卡;03:白金卡.');
//            $table->double('now_point')->default(0)->comment('用户现有积分');

            //停车相关信息
            $table->string('parking_no')->nullable()->comment('用户车牌');
            $table->timestamp('bind_car_at')->nullable()->comment('车牌绑定时间');
            $table->timestamp('last_park_time')->nullable()->comment('上一次停车时间');

            $table->integer('preferential_parking_times')->default(0)->comment('可使用优惠停车次数/每天(已使用)');

            $table->integer('free_park_time')->default(0)->comment('免费停车时间(已使用)');

            $table->double('integral_parking_money')->default(0)->comment('使用积分抵扣的停车金额/每周(已使用)');
            $table->double('member_parking_money')->default(0)->comment('直接抵扣的停车金额/每周(已使用)');

            //环信
            $table->string('easemob_id')->nullable()->comment('环信id');
            $table->string('easemob_username')->nullable()->comment('环信用户名');
            $table->string('easemob_password')->nullable()->comment('环信密码');

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            //索引
            $table->index([ 'subject_id', 'parking_no' ]);

            $table->unique([ 'subject_id', 'mobile' ]);
            $table->unique([ 'subject_id', 'email' ]);
            $table->unique([ 'subject_id', 'username' ]);

        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
