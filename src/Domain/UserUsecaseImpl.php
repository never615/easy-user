<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Str;
use Mallto\Tool\Domain\WechatUsecase;
use Mallto\Tool\Exception\NotFoundException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Data\User;
use Mallto\User\Data\UserAuth;
use Mallto\User\Data\UserProfile;

/**
 * 默认版的用户处理
 *
 * 适合微信开放平台关联的
 *
 * Created by PhpStorm.
 * User: never615
 * Date: 06/06/2017
 * Time: 7:57 PM
 */
class UserUsecaseImpl implements UserUsecase
{
    /**
     * @var WechatUsecase
     */
    protected $wechatUsecase;

    /**
     * UserUsecaseImpl constructor.
     */
    public function __construct(WechatUsecase $wechatUsecase)
    {
        $this->wechatUsecase = $wechatUsecase;
    }


    /**
     * 从请求中提取用户凭证
     *
     * @param $request
     * @return array
     */
    public function transformCredentials($request)
    {
        $credentials = [
            'identityType' => $request->get("identity_type"),
            'identifier'   => $request->get("identifier"),
            '$requestType' => $request->header("REQUEST-TYPE"),
        ];

        if (!empty($request->get('credential'))) {
            $credentials['credential'] = $request->get('credential');
        }

        return $credentials;
    }

    /**
     * 给user对象添加token
     * token分不同的类型,即不同的作用域,比如有普通的微信令牌和绑定手机的微信用户的令牌
     *
     * @param $user
     */
    public function addToken($user)
    {
        if (!$user) {
            throw new NotFoundException("用户不存在");
        }

        if (!empty($user->mobile)) {
            $token = $user->createToken(config("app.unique"), ["mobile-token"])->accessToken;
        } else {
            $token = $user->createToken(config("app.unique"), ["wechat-token"])->accessToken;
        }

        $user->token = $token;

        return $user;
    }


    /**
     * 根据标识符检查用户是否通过验证
     *
     * 1. 登录的时候需要用
     * 2. 注册的时候也可以使用来判断用户是否存在
     *
     * @param $credentials
     * @param $subject
     * @return User|null
     */
    public function retrieveByCredentials($credentials, $subject)
    {
        if (empty($credentials)) {
            return null;
        }

        $requestType = $credentials['requestType'];
        $identityType = $credentials['identityType'];
        $identifier = $credentials['identifier'];

        switch ($requestType) {
            case "WECHAT":
                $identifier = $this->decryptOpenid($identifier);
                break;
        }


        $query = UserAuth::where("identity_type", $identityType)
            ->where("identifier", $identifier)
            ->where("subject_id", $subject->id);

        foreach ($credentials as $key => $value) {
            if (!Str::contains($key, 'credential')) {
                $query->where($key, $value);
            }
        }

        $userAuth = $query->first();

        if (!$userAuth) {
            return null;
        }

        $user = $userAuth->user;
        if (!$user) {
            //userAuth存在,user不存在,系统异常
            \Log::error("异常:userAuth存在,user不存在,");

            return null;
        }


        return $userAuth->user;
    }


    const SUPPORT_BIND_TYPE = ['mobile'];


    /**
     * 检查用户的绑定状态
     * 存在对应绑定项目,返回true;否则返回false
     *
     * @param $user
     * @param $bindType
     * @return bool
     */
    public function checkUserBindStatus($user, $bindType)
    {
        return empty($user->$bindType) ? false : true;
    }


    /**
     * 检查对应项是否已经被绑定,注册可用
     * 如:检查手机号是否被绑定
     *
     * @param $bindDate
     * @param $bindType
     * @param $subjectId
     */
    public function isBinded($bindDate, $bindType, $subjectId)
    {
        return User::where($bindType, $bindDate)
            ->where("subject_id", $subjectId)
            ->exist();
    }


    /**
     * 绑定数据(如:手机,邮箱)
     *
     * @param $user
     * @param $bindType
     * @param $bindData
     * @return mixed
     */
    public function bind($user, $bindType, $bindData)
    {
        $user->$bindType = $bindData;
        $user->save();

        return $user;
    }


    /**
     * 微信:创建用户
     *
     * @param      $credentials
     * @param      $subject
     * @param null $info
     * @return User
     */
    public function createUserByWechat($credentials, $subject, $info = null)
    {
        if (empty($credentials)) {
            throw new ResourceException("异常请求:credentials为空");
        }

        $identityType = $credentials['identityType'];
        $identifier = $credentials['identifier'];
        $credential = null;
        foreach ($credentials as $key => $value) {
            if (!Str::contains($key, 'credential')) {
                $credential = $value;
            }
        }
        $wechatUserInfo = $this->wechatUsecase->getWechatUserInfo($subject->uuid,
            $this->decryptOpenid($credentials['credential']));

        $userData = [
            'subject_id' => $subject->id,
            'nickname'   => $wechatUserInfo->nickname,
            "avatar"     => $wechatUserInfo->avatar,
        ];

        \DB::beginTransaction();

        $user = User::create($userData);

        $user->userAuths()->create([
            'subject_id'    => $subject->id,
            'identity_type' => $identityType,
            'identifier'    => $identifier,
            'credential'    => $credential,
        ]);

        \DB::commit();

        return $user;
    }


    /**
     * 解密openid
     *
     * @param $openid
     * @return string
     */
    public function decryptOpenid($openid)
    {
        try {
            $openid = urldecode($openid);
            $openid = decrypt($openid);

            return $openid;
        } catch (DecryptException $e) {
            \Log::error("openid解密失败:".$openid);
            throw new ResourceException("openid无效");
        }
    }


    /**
     * 获取用户信息
     * 默认实现即返回用户对象,不同的项目可以需要返回的不一样.
     * 比如:mall项目需要返回member信息等,不同项目可以有自己不同的实现
     *
     * @param $user
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function getReturenUserInfo($user)
    {
        return $this->addToken($user);
    }


    /**
     * 更新用户的微信信息
     *
     * @param $user
     * @param $credentials
     * @param $subject
     */
    public function updateUserWechatInfo($user, $credentials, $subject)
    {
        $wechatUserInfo = $this->wechatUsecase->getWechatUserInfo($subject->uuid,
            $this->decryptOpenid($credentials['credential']));
        $this->updateOrCreateUserProfile($user, $wechatUserInfo);
    }


    /**
     * 更新或者创建用户的微信信息
     *
     * @param $user
     * @param $wechatUserInfo
     */
    private function updateOrCreateUserProfile($user, $wechatUserInfo)
    {
        UserProfile::updateOrCreate(['user_id' => $user->id],
            [
                "wechat_nickname"  => $wechatUserInfo->nickname,
                "wechat_avatar"    => $wechatUserInfo->avatar,
                "wechat_province"  => $wechatUserInfo->province,
                "wechat_city"      => $wechatUserInfo->city,
                "wechat_country"   => $wechatUserInfo->country,
                "wechat_sex"       => $wechatUserInfo->sex,
                "wechat_language"  => $wechatUserInfo->language,
                "wechat_privilege" => $wechatUserInfo->privilege,
            ]
        );

    }

}
