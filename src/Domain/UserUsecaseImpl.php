<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mallto\Tool\Exception\NotFoundException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Utils\AppUtils;
use Mallto\User\Data\Repository\UserAuthRepository;
use Mallto\User\Data\Repository\UserAuthRepositoryInterface;
use Mallto\User\Data\User;
use Mallto\User\Data\UserAuth;
use Mallto\User\Data\UserProfile;
use Mallto\User\Data\UserSalt;
use Mallto\User\Exceptions\UserAuthExistException;
use Mallto\User\Jobs\UpdateWechatUserInfoJob;

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
     * @var UserAuthRepository
     */
    private $userAuthRepository;


    /**
     * UserUsecaseImpl constructor.
     *
     * @param UserAuthRepositoryInterface $userAuthRepository
     */
    public function __construct(UserAuthRepositoryInterface $userAuthRepository)
    {
        $this->userAuthRepository = $userAuthRepository;
    }


    /**
     * 根据授权标识符查询是否存在对应的用户
     *
     * @param Request $request
     * @param         $subject
     *
     * @return User|null
     */
    public function retrieveByRequestCredentials(Request $request, $subject)
    {
        $credentials = $this->transformCredentialsFromRequest($request);

        return $this->retrieveByCredentials($credentials, $subject);
    }


    /**
     * 从请求中提取用户凭证
     *
     * @param      $request
     *
     * @return array
     */
    public function transformCredentialsFromRequest($request)
    {
        $credentials = [
            'identityType' => $request->get("identity_type") ?? "wechat",
            'identifier'   => $request->get("identifier"),
            'requestType'  => $request->header("REQUEST-TYPE"),
            'credential'   => $request->get('credential'),

        ];

        return $credentials;
    }


    /**
     * 根据授权标识符查询是否存在对应的用户
     *
     * 1. 登录的时候需要用
     * 2. 注册的时候也可以使用来判断用户是否存在
     *
     * @param $credentials
     * @param $subject
     *
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

        $userAuth = $query->first();
        if ( ! $userAuth) {
            return null;
        }

        if (isset($credentials["credential"]) && $credentials["credential"]) {
            if ( ! \Hash::check($credentials["credential"], $userAuth->credential)) {
                return null;
            }
        }

        $user = User::where("id", $userAuth->user_id)->first();

        if ( ! $user) {
            //userAuth存在,user不存在,系统异常
            \Log::error("异常:userAuth存在,user不存在,");

            return null;
        }

        return $user;
    }


    /**
     * 给user对象添加token
     * token分不同的类型,即不同的作用域,比如有普通的微信令牌和绑定手机的微信用户的令牌
     *
     * @param $user
     *
     * @return mixed
     */
    public function addToken($user)
    {
        if ( ! $user) {
            throw new NotFoundException("用户不存在");
        }

        if ( ! empty($user->mobile)) {
            $token = $user->createToken(config("app.unique"), [ "mobile-token" ])->accessToken;
        } else {
            $token = $user->createToken(config("app.unique"), [ "wechat-token" ])->accessToken;
        }

        $user->token = $token;

        return $user;
    }


    /**
     * 检查用户的绑定状态
     * 存在对应绑定项目,返回true;否则返回false
     *
     * @param $user
     * @param $bindType
     *
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
     *
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
     *
     * @return
     */
    public function hasUserAuth($user, $identityType)
    {
        $userAuth = $user->userAuths()
            ->where("identity_type", $identityType)
            ->first();

        return $userAuth;
    }


    /**
     * 绑定数据(如:手机,邮箱)
     * 主要是微信注册调用
     *
     * @param $user
     * @param $bindType
     * @param $bindData
     *
     * @return mixed
     */
    public function bind($user, $bindType, $bindData)
    {
        if ( ! in_array($bindType, User::SUPPORT_BIND_TYPE)) {
            throw new ResourceException("无效的绑定凭证:" . $bindData);
        }
        $user->$bindType = $bindData;
        $user->save();

        return $user;
    }


    /**
     * 创建用户
     *
     * @param        $credentials
     * @param        $subject
     * @param array  $info
     * @param string $form
     * @param string $fromAppId 第三方注册时的appid
     *
     * @return User
     * @throws AuthenticationException
     */
    public function createUser(
        $credentials,
        $subject,
        $info = [],
        $form = "wechat",
        $fromAppId = null
    ) {
        if (empty($credentials)) {
            throw new ResourceException("异常请求:credentials为空");
        }

        $mobile = null;
        $userData = [
            'subject_id' => $subject->id,
        ];
        switch ($credentials["identityType"]) {
            case "mobile":
            case "sms":
                $userData = [
                    'mobile'     => $credentials['identifier'],
                    'subject_id' => $subject->id,
                ];

                $mobile = $credentials['identifier'];
                break;
            case "wechat":
                //不实时获取设置微信信息
                //$wechatUserInfo = $this->getWechatUserInfo($credentials['identifier'], $subject);
                //$userData = [
                //    'nickname'   => $wechatUserInfo['nickname'] ?? null,
                //    "avatar"     => $wechatUserInfo['avatar'] ?? null,
                //];
                break;
            default:
                throw new ResourceException("无效的user auth类型:" . $credentials["identityType"]);
                break;
        }

        \DB::beginTransaction();

        $userData["status"] = "normal";
        $userData["from"] = $form;
        $userData["from_third_app_id"] = $fromAppId;
        $userData["is_register_gift"] = false;

        try {
            $user = User::create($userData);
        } catch (\PDOException $e) {
            // Handle integrity violation SQLSTATE 23000 (or a subclass like 23505 in Postgres) for duplicate keys
            if (0 === strpos($e->getCode(), '23505') && $mobile) {
                //检查如果已存在
                DB::rollBack();
                $user = User::where([
                    'subject_id' => $subject->id,
                    'mobile'     => $mobile,
                ])->first();
                if ($user) {

                    return $user;
                }

                throw $e;
            }
        }

        //如果userAuth没有创建则创建
        try {
            $userAuth = $this->createUserAuth($credentials, $user);
            DB::commit();
        } catch (UserAuthExistException $userAuthExistException) {
            DB::rollBack();
            $user = $this->retrieveByCredentials($credentials, $subject);
            if ( ! $user) {
                throw $userAuthExistException;
            }
        }

        if ($credentials['identityType'] === 'wechat') {
            $this->updateUserWechatInfo($user, $credentials, $subject);
        }

        return $user;
    }


    /**
     * 创建用户授权信息
     *
     * @param      $credentials
     * @param      $user
     * @param bool $openidEncrypted
     *
     * @return
     */
    public
    function createUserAuth(
        $credentials,
        $user,
        $openidEncrypted = true
    ) {
        $identityType = $credentials["identityType"];
        $identifier = $credentials['identifier'];

        switch ($identityType) {
            case "wechat":
                $this->userAuthRepository->create(array_merge($credentials, [
                    'identifier' => ($openidEncrypted ? AppUtils::decryptOpenid($identifier) : $identifier),
                ]), $user);

                break;
            case "mobile":
                //如果是手机绑定,均添加sms的验证方式
                $this->createUserAuth([
                    "identifier"    => $identifier,
                    "identity_type" => "sms",
                ], $user);

                $this->userAuthRepository->create($credentials, $user);
                break;
            case "sms":
                $this->userAuthRepository->create($credentials, $user);
                break;
            default:
                $this->userAuthRepository->create($credentials, $user);
                break;
        }

        return $user;
    }


    /**
     * 注册成功之后执行
     * 可以在执行注册送礼等需要等逻辑
     *
     * @param $user
     */
    public
    function registerGift(
        $user
    ) {

    }


    /**
     * 更新用户信息
     *
     * @param $user
     * @param $info
     *
     * @return mixed
     */
    public
    function updateUser(
        $user,
        $info
    ) {
        $user->nickname = $info['name'] ?? null;
        $user->avatar = $info['avatar'] ?? null;

        if ( ! $user->userProfile) {
            UserProfile::create([
                "user_id" => $user->id,
                "gender"  => 0,
            ]);
        }

        $user->userProfile->birthday = $info['birthday'] ?? null;
        $user->userProfile->gender = $info['gender'] ?? null;

        $user->save();
        $user->userProfile->save();

        return $user;
    }


    /**
     * 解密openid
     *
     * @param $openid
     *
     * @return string
     * @deprecated
     *
     */
    public
    function decryptOpenid(
        $openid
    ) {
        return AppUtils::decryptOpenid($openid);
    }


    /**
     * 获取用户信息
     * 默认实现即返回用户对象,不同的项目可以需要返回的不一样.
     * 比如:mall项目需要返回member信息等,不同项目可以有自己不同的实现
     *
     * @param      $user
     * @param bool $addToken
     * @param bool $wechatLogin
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User
     */
    public
    function getReturnUserInfo(
        $user,
        $addToken = true,
        $wechatLogin = false
    ) {
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
     * @param       $user
     * @param       $credentials
     * @param       $subject
     * @param array $wechatUserInfo
     */
    public
    function updateUserWechatInfo(
        $user,
        $credentials,
        $subject,
        $wechatUserInfo = null
    ) {
        if ($wechatUserInfo) {
            UserProfile::updateOrCreate([ 'user_id' => $user->id ],
                [
                    "wechat_user" => $wechatUserInfo ?? null,
                ]
            );
        } else {
            dispatch(new UpdateWechatUserInfoJob($credentials['identifier'],
                $user->id, $subject))->delay(Carbon::now()->addMinutes(1));
        }
    }


    /**
     * 合并用户
     *
     * 把两个用户账户合并,一般是用户已经是纯微信用户,且已经使用手机注册了app.
     *
     * 此时需要用户在微信要绑定手机,则需要合并两个用户
     *
     * 要做的事情:把用户的所有相关的业务数据的user_id都改成同一个,然后删除废弃用户
     *
     * @param $appUser
     * @param $wechatUser
     *
     * @return mixed|null
     */
    public
    function mergeAccount(
        $appUser,
        $wechatUser
    ) {
        \Log::warning('mergeAccount');
        \Log::warning($appUser);
        \Log::warning($wechatUser);

        DB::begintransaction();
        $wechatUserAuth = $wechatUser->userAuths()
            ->where("identity_type", 'wechat')
            ->first();

        $wechatUserIdentityType = $wechatUserAuth->identity_type;
        $wechatUserIdentifier = $wechatUserAuth->identifier;

        //1. 把微信用户的业务数据合并
        //目前合并只有海上世界项目会出现,海上世界的纯微信用户没有需要合并的业务,所以不需要处理

        //2. 删除wechatUser
        $wechatUser->delete();

        //3. 合并wechatUser的授权方式到appUser
        $appUser = $this->createUserAuth([
            'identityType' => $wechatUserIdentityType,
            'identifier'   => $wechatUserIdentifier,
        ], $appUser, false);

        DB::commit();

        return $appUser;
    }


    /**
     * 处理其他用户信息
     *
     * @param $user
     * @param $info
     *
     * @return mixed
     */
    public
    function bindOrCreateByOtherInfo(
        $user,
        $info
    ) {

    }


    /**
     * 获取微信用户
     *
     * @param $openid
     * @param $subject
     *
     * @return
     * @throws AuthenticationException
     */
    protected
    function getWechatUserInfo(
        $openid,
        $subject
    ) {
        //$uuid = $subject->uuid;
        $wechatUsecase = app(\Mallto\User\Domain\WechatUsecase::class);

        $wechatUserInfo = $wechatUsecase->getUserInfo(
            $subject->wechat_uuid ?? $subject->uuid,
            AppUtils::decryptOpenid($openid));

        return $wechatUserInfo;
    }


    public
    function bindSalt(
        $user,
        $saltId
    ) {
        UserSalt::where('id', $saltId)->update([ 'user_id', $user->id ]);
    }


    /**
     * 检查用户状态
     *
     * @param $user
     *
     * @return mixed
     */
    public
    function checkUserStatus(
        $user
    ) {
        // TODO: Implement checkUserStatus() method.
    }


    /**
     * 通过手机号创建用户
     *
     * @param string  $from 注册来源
     * @param         $mobile
     * @param         $subject
     * @param array   $userInfo
     * @param         $memberCardId
     * @param         $memberLevelId
     * @param null    $appId
     *
     * @return \Mallto\User\Data\User
     */
    public
    function createByMobile(
        $from,
        $mobile,
        $subject,
        $userInfo = [],
        $memberCardId = null,
        $memberLevelId = null,
        $appId = null
    ) {
        // TODO: Implement createByMobile() method.
    }

}
