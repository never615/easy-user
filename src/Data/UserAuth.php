<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Data;


use Illuminate\Database\Eloquent\Model;

/**
 * 验证用户
 * Class UserAuth
 *
 * @package App\Module\User
 */
class UserAuth extends Model
{
//    /**
//     * The attributes that are mass assignable.
//     *
//     * @var array
//     */
//    protected $fillable = [
//        'user_id',
//        'identity_type',    //登录类型（手机号 邮箱 用户名）或第三方应用名称（微信 微博等）
//        'identifier',       //标识（手机号 邮箱 用户名或第三方应用的唯一标识）
//        'credential',        //密码凭证（站内的保存密码，站外的不保存或保存token）
//    ];

    protected $guarded = [
    
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
