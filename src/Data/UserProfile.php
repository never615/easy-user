<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Data;


use Encore\Admin\Auth\Database\Traits\BaseModel;

/**
 * 用户资料
 * Class UserAuth
 *
 * @package App\Module\User\UserProfile
 */
class UserProfile extends BaseModel
{

//    /**
//     * The attributes that are mass assignable.
//     *
//     * @var array
//     */
//    protected $fillable = [
//        'user_id',
//        'wechat_nickname',
//        'wechat_avatar',
//        'wechat_province',
//        'wechat_city',
//        'wechat_country',
//        'wechat_sex',
//        'wechat_language',
//        'wechat_privilege',
//        'wechat_unionid',
//    ];


    protected $hidden = [
        'wechat_nickname',
        'wechat_avatar',
        'wechat_province',
        'wechat_city',
        'wechat_country',
        'wechat_sex',
        'wechat_language',
        'wechat_privilege',
        'wechat_unionid',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'extra'       => 'array',
        'wechat_user' => 'array',
    ];


    protected $guarded = [];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
