<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * 用户登录授权信息表
 * 用户的所有登录授权信息均均存于此表,包括:用户名/手机号/邮箱和第三方登录等.
 * 通过user_id与users表关联,一个用户的多个登录凭证对应多条记录保存于此.
 * Class CreateUserAuthsTable
 */
class CreateUserAuthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_auths', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->string('identity_type')->comment('登录类型,约定如下.用户名:username;手机:mobile;邮箱:email;微博:weibo;微信:weixin');
            $table->string('identifier')->comment('登录校验的唯一标识,如手机号/用户名或者第三方登录标识');
            $table->string('credential')->nullable()->comment('登录凭证,站内登录保存密码,站外的不保存或者保存token');

            $table->integer('subject_id')->comment('主体id');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('CASCADE');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_id']);
            
            $table->unique(['subject_id',"identity_type","identifier"]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_auths');
    }
}
