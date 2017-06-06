<?php
namespace Mallto\User\Controller\Api\Auth;

use App\Exceptions\PermissionDeniedException;
use App\Exceptions\ResourceException;
use Encore\Admin\AppUtils;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Mallto\User\Data\User;
use Mallto\User\Data\UserAuth;
use Mallto\User\Data\WechatAuthInfo;
use Mallto\User\Data\WechatUserInfo;
use Overtrue\LaravelWechat\Model\WechatCorpAuth;
use Overtrue\LaravelWechat\Model\WechatCorpUserInfo;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class WechatLoginController extends \Illuminate\Routing\Controller
{

    /**
     * 根据openId登录
     */
    public function loginByOpenid()
    {
        return $this->wechatLoginInter();
    }


    /**
     * 微信登录接口,用户需要绑定了type字段代码的东西,
     * 比如:type 为mobile,用户表中则需要有mobile的内容,还可以是email等
     *
     * @param $type
     * @return AuthenticationException
     */
    public function wechatLoginWithType($type)
    {
        return $this->wechatLoginInter($type);

    }

    private function wechatLoginInter($type = null)
    {
        $openId = Input::get("open_id");
        try {
            $openId = decrypt($openId);
        } catch (DecryptException $e) {
            Log::warning($openId);
            Log::error("openid解密失败");
            throw new ResourceException("openid无效");
        }

        $subject = AppUtils::getSubject();

        $uuid = AppUtils::getUUID();

        //先判断该openId有没有对应的用户

        //根据openId,查询微信用户信息
        $userAuth = UserAuth::where("identity_type", "wechat")
            ->where("identifier", $openId)
            ->where("subject_id", $subject->id)
            ->first();
        if (!$userAuth) {
            //用户不存在
            //查询微信用户信息
            $wechatAuthInfo = WechatAuthInfo::where("uuid", $uuid)->first();
            if (!$wechatAuthInfo) {
                throw new PermissionDeniedException("公众号未授权");
            }

            $wechatUserInfo = WechatUserInfo::where("openid", $openId)
                ->where("app_id", $wechatAuthInfo->authorizer_appid)
                ->first();

            if (!$wechatUserInfo) {
                Log::error("无法获取微信信息");

                return new PermissionDeniedException("无法获取微信信息,请在微信内打开");
//                return new  InternalHttpException("系统错误");
            }
            //创建微信用户
            $user = User::create([
                "subject_id" => $subject->id,
                "nickname"   => $wechatUserInfo->nickname,
                "avatar"     => $wechatUserInfo->avatar,
            ]);

            $user->userAuths()->create([
                "identity_type" => "wechat",
                "identifier"    => $openId,
                "subject_id"    => $subject->id,
            ]);

            //填充微信信息
            $user->userProfile()->create([
                "wechat_nickname"  => $wechatUserInfo->nickname,
                "wechat_avatar"    => $wechatUserInfo->avatar,
                "wechat_province"  => $wechatUserInfo->province,
                "wechat_city"      => $wechatUserInfo->city,
                "wechat_country"   => $wechatUserInfo->country,
                "wechat_sex"       => $wechatUserInfo->sex,
                "wechat_language"  => $wechatUserInfo->language,
                "wechat_privilege" => $wechatUserInfo->privilege,
            ]);

        } else {
            $user = $userAuth->user;
        }

        if ($type) {
            if (is_null($user->$type)) {
                //用户不存在
                Log::error("用户不存在");

                return new  AuthenticationException();
            }
        }
        $user = User::find($user->id);

        if ($type) {
            $token = $user->createToken("easy", ["mobile-token"])->accessToken;
        } else {
            $token = $user->createToken("easy", ["wechat-token"])->accessToken;
        }

        return response()->json([
            "token" => $token,
        ]);
    }


    /**
     * 微信企业号使用,根据userid登录
     */
    public function loginByCorp()
    {
        $openId = Input::get("open_id");
        try {
            $openId = decrypt($openId);
        } catch (DecryptException $e) {
            Log::warning($openId);
            Log::error("openid解密失败");
            throw new ResourceException("openid无效");
        }

        $subject = AppUtils::getSubject();

        $uuid = AppUtils::getUUID();

        //先判断该openId有没有对应的用户

        //根据openId,查询微信用户信息
        $userAuth = UserAuth::where("identity_type", "wechat")
            ->where("identifier", $openId)
            ->where("subject_id", $subject->id)
            ->first();
        if (!$userAuth) {
            //用户不存在
            //查询微信用户信息
            $wechatAuthInfo = WechatCorpAuth::where("corp_id", $uuid)->first();
            if (!$wechatAuthInfo) {
                throw new PermissionDeniedException("企业号未授权");
            }

            $wechatUserInfo = WechatCorpUserInfo::where("user_id", $openId)
                ->where("corp_id", $wechatAuthInfo->corp_id)
                ->first();

            if (!$wechatUserInfo) {
                Log::error("无法获取微信用户信息");

                return new PermissionDeniedException("无法获取微信用户信息,请在微信内打开");
            }
            //创建微信用户
            $user = User::create([
                "subject_id" => $subject->id,
                "nickname"   => $wechatUserInfo->name,
                "avatar"     => $wechatUserInfo->avatar,
            ]);

            $user->userAuths()->create([
                "identity_type" => "wechat",
                "identifier"    => $openId,
                "subject_id"    => $subject->id,
            ]);

            //填充微信信息

        } else {
            $user = $userAuth->user;
        }

        $user = User::find($user->id);
        $token = $user->createToken("qy", ["wechat-token"])->accessToken;

        return response()->json([
            "token" => $token,
        ]);
    }

}
