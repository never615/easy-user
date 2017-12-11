<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Str;
use Mallto\Tool\Exception\NotFoundException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Data\User;
use Mallto\User\Data\UserAuth;
use Mallto\User\Data\UserProfile;
use Mallto\User\Data\UserSalt;
use Overtrue\LaravelWechat\Domain\WechatUsecase;

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
     * 从请求中提取用户凭证
     *
     * @param      $request
     * @param bool $credential
     * @return array
     */
    public function transformCredentialsFromRequest($request, $credential = false)
    {
        $credentials = [
            'identityType' => $request->get("identity_type"),
            'identifier'   => $request->get("identifier"),
            'requestType'  => $request->header("REQUEST-TYPE"),
        ];

        if ($credential && $request->get('credential')) {
            $credentials["credential"] = $request->get('credential');
        }

        return $credentials;
    }


    /**
     * 从请求中提取用户凭证
     *
     * @param      $identityType
     * @param bool $identifier
     * @param null $requestType
     * @param null $credential
     * @return array
     */
    public function transformCredentials($identityType, $identifier, $requestType = null, $credential = null)
    {
        $credentials = [
            'identityType' => $identityType,
            'identifier'   => $identifier,
            'requestType'  => $requestType,
        ];

        if (!empty($credential)) {
            $credentials["credential"] = $credential;
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
            if (Str::contains($key, 'credential')) {
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
     * @param $bindType
     * @param $bindDate
     * @param $subjectId
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function isBinded($bindType, $bindDate, $subjectId)
    {
        return User::where($bindType, $bindDate)
            ->where("subject_id", $subjectId)
            ->first();
    }

    /**
     * 检查用户是否有对应的凭证类型
     *
     * @param $identityType
     */
    public function hasIdentityType($user, $identityType)
    {
        return $user->userAuths()
            ->where("identity_type", $identityType)
            ->first();
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
            if (Str::contains($key, 'credential')) {
                $credential = $value;
            }
        }
        $wechatUserInfo = $this->getWechatUserInfo($credentials['identifier'], $subject);

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
            'identifier'    => $this->decryptOpenid($identifier),
            'credential'    => $credential,
        ]);
        \DB::commit();

        return $user;
    }


    /**
     * app注册创建用户
     *
     * @param      $credentials
     * @param      $subject
     * @param null $info
     * @return User
     */
    public function createUserByApp($credentials, $subject, $info = null)
    {
        if (empty($credentials)) {
            throw new ResourceException("异常请求:credentials为空");
        }

        $identityType = $credentials['identityType'];
        $identifier = $credentials['identifier'];
        $credential = null;
        foreach ($credentials as $key => $value) {
            if (Str::contains($key, 'credential')) {
                $credential = $value;
            }
        }

        $userData = [
            $credentials["identityType"] => $credentials['identifier'],
            'subject_id'                 => $subject->id,
        ];

        \DB::beginTransaction();


        $user = User::create($userData);

        //保存$credential的时候再进行一次加密
        $hashCreential = \Hash::make($credential);

        $user->userAuths()->create([
            'subject_id'    => $subject->id,
            'identity_type' => $identityType,
            'identifier'    => $identifier,
            'credential'    => $hashCreential,
        ]);

        \DB::commit();

        return $user;
    }


    /**
     * 注册成功之后执行
     * 可以在执行注册送礼等需要等逻辑
     *
     * @param $user
     */
    public function createSuccess($user)
    {

    }

    /**
     * 增加授权方式
     *
     * @param $user
     * @param $credentials
     */
    public function addIdentifier($user, $credentials)
    {
        $hashCreential = null;
        if (isset($credentials["credential"])) {
            $hashCreential = \Hash::make($credentials["credential"]);
        }


        $user->userAuth()->create([
            "identifier"    => $credentials['identifier'],
            "identity_type" => $credentials["identity_type"],
            "credential"    => $hashCreential,
        ]);

    }


    /**
     * 更新用户信息
     *
     * @param $user
     * @param $info
     */
    public function updateUser($user, $info)
    {
        isset($info['name']) ? $user->nickname = $info['name'] : null;
        isset($info['avatar']) ? $user->avatar = $info['avatar'] : null;

        if (!$user->userProfile) {
            UserProfile::create([
                "user_id"=>$user->id
            ]);
        }

        $birthDay = isset($info['birthday']) ? $user->userProfile->birthday = $info['birthday'] : null;
        $gender = isset($info['gender']) ? $user->userProfile->gender = $info['gender'] : null;

        $user->save();
        $user->userProfile->save();

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
     * @param      $user
     * @param bool $addToken
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User
     */
    public function getReturenUserInfo($user, $addToken = true)
    {
        $user = User::with("userProfile")
            ->findOrFail($user->id);

        if ($addToken) {
            $user = $this->addToken($user);
        }

        return $user;
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
        $wechatUserInfo = $this->getWechatUserInfo($credentials['identifier'], $subject);


        UserProfile::updateOrCreate(['user_id' => $user->id],
            [
                "wechat_user" => $wechatUserInfo->toArray(),
            ]
        );

//        $this->updateOrCreateUserProfileByWechat($user, $wechatUserInfo);
    }

    /**
     * 合并用户
     *
     * 把两个用户账户合并,一般是用户已经是纯微信用户,且已经使用手机注册了app.此时需要用户在微信要绑定手机,则需要合并两个用户
     *
     * 要做的事情:把用户的所有相关的业务数据的user_id都改成同一个,然后删除废弃用户
     *
     * @param $appUser
     * @param $wechatUser
     */
    public function mergeAccount($appUser, $wechatUser)
    {
        \Log::error("mergeAccount");
        \Log::error($appUser);
        \Log::error($wechatUser);
        $wechatUserAuth = $wechatUser->userAuths()
            ->where("identity_type", 'wechat')
            ->first();
        //1. 合并wechatUser的授权方式到appUser
        $appUser = $this->addIdentifier($appUser, [
            "identityType" => $wechatUserAuth->identity_type,
            "identifier"   => $wechatUserAuth->identifier,
        ]);
        //2. 删除wechatUser
        $wechatUser->delete();

        return $appUser;
    }

//    /**
//     * 更新或者创建用户的微信信息
//     *
//     * @param $user
//     * @param $wechatUserInfo
//     */
//    private function updateOrCreateUserProfileByWechat($user, $wechatUserInfo)
//    {
//        UserProfile::updateOrCreate(['user_id' => $user->id],
//            [
//                "wechat_user" => $wechatUserInfo->toArray(),
//            ]
//        );
//    }


    /**
     * 处理其他用户信息
     *
     * @param $user
     * @param $info
     * @return mixed
     */
    public function bindOtherInfo($user, $info)
    {

    }

    /**
     * 获取微信用户
     *
     * @param $openid
     * @param $subject
     */
    protected function getWechatUserInfo($openid, $subject)
    {
        $wechatUsecase = app(WechatUsecase::class);

        $wechatUserInfo = $wechatUsecase->getWechatUserInfo($subject->uuid,
            $this->decryptOpenid($openid));

        return $wechatUserInfo;
    }

    public function bindSalt($user, $saltId)
    {
        UserSalt::where('id', $saltId)->update(['user_id', $user->id]);
    }

}
