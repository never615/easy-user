<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Data;


use Illuminate\Database\Eloquent\Model;

/**
 * 用户资料
 * Class UserAuth
 *
 * @package App\Module\User\UserProfile
 */
class UserProfile extends Model
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

    protected $casts = [
        'extra' => 'array',
    ];


    protected $guarded = [];

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
