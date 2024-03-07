<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Data;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Mallto\Admin\Data\Subject;
use Mallto\Admin\Data\Traits\DynamicData;
use Mallto\Admin\Data\Traits\SelectSource;
use Mallto\Tool\Domain\Traits\TagTrait;

class User extends Authenticatable
{

    use HasApiTokens, Notifiable, DynamicData, SelectSource, \Mallto\User\Domain\Traits\UserAuthTrait, TagTrait;

    //用户标识(状态),如:注册中(用户信息待完善),注册中(用户标签待完善),黑名单等
    const STATUS = [
        "normal" => "正常用户",
        "blacklist" => "黑名单用户",
    ];

    /**
     * 支持绑定的字段
     */
    const SUPPORT_BIND_TYPE = ['mobile'];

    /**
     * 注册来源
     */
    const REGISTER_FROM = [
        "wechat" => "微信注册",
        "app" => "app注册",
        "admin" => "管理端创建",
        "admin_import" => "管理端批量导入创建",
        "third_part" => "第三方系统注册",
        'qrcode' => "二维码注册",
        'ali' => "支付宝注册",
    ];

    /**
     * 用户信息接口需要展示的字段
     *
     * @var array
     */
    protected $info = [
        "id",
        "email",
        "nickname",
    ];

    protected $guarded = [

    ];

    protected $casts = [
        'wechat_user' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
        'easemob_id',
        'easemob_password',
        'easemob_username',
        'remember_token',
    ];


    public static function selectSourceDate()
    {
        return static::dynamicData()->pluck("nickname", "id");
    }


    /**
     *
     * 获取用户信息接口需要返回的字段
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }


    public function userAuths()
    {
        return $this->hasMany(UserAuth::class);
    }


    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }


    public function topSubject()
    {
        return $this->belongsTo(Subject::class, "top_subject_id");
    }


    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }


    public function userSalt()
    {
        return $this->hasOne(UserSalt::class);
    }


    public function getAvatarAttribute($value)
    {
        if (request()->header("mode") === "api") {
            if (empty($value)) {
                $user = User::find($this->id);
                if ($user && $user->userProfile && $user->userProfile->wechat_user) {
                    return $user->userProfile->wechat_user['avatar'];
                }

                return null;
            }

            if (starts_with($value, "http")) {
                return $value;
            }
        } else {
            if ($value) {
                if (starts_with($value, "http")) {
                    return $value;
                } else {
                    return config("app.file_url_prefix") . $value;
                }
            } else {
                return $value;
            }
        }
    }

}
