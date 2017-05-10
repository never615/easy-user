<?php

namespace Mallto\User\Data;


use Illuminate\Database\Eloquent\Model;


class WechatAuthInfo extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection;


    protected $fillable = [
        'authorizer_appid',
        'authorizer_access_token',
        'authorizer_refresh_token',
        'nick_name',
        'service_type_info',
        'verify_type_info',
        'user_name',
        'principal_name',
        'business_info',
        'alias',
        'qrcode_url',
        'func_info',
        'authorization_code',
    ];

    /**
     * WechatAuthInfo constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $connection = config('wechat.connection_name') ?: config('database.default');

        $this->setConnection($connection);
        parent::__construct($attributes);
    }
    
    public function users(){
        return $this->hasMany(WechatUserInfo::class);
    }

}
