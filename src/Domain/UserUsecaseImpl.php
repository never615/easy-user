<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use Encore\Admin\AppUtils;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Data\User;
use Mallto\User\Data\UserAuth;
use Mallto\User\Data\WechatAuthInfo;
use Mallto\User\Data\WechatUserInfo;
use Mallto\User\Exceptions\UserExistException;

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
     * 判断用户是否存在
     *
     * @param        $type
     * @param bool   $register ,注册或者登录模式,
     *                         当type是mobile的情况下:
     *                         注册模式下还需要判断注册的手机号是否已经存在
     * @param string $requestType
     * @return bool|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Query\Builder|UserAuth|UserUsecaseImpl
     */
    public function existUser($type = null, $register = true, $requestType = "")
    {
        $requestType = $requestType ?: Request::header("REQUEST-TYPE");
        $subject = AppUtils::getSubject();

        if ($requestType == "WECHAT" || $requestType == 'bridge') {
            $openid = $this->getOpenid();
            //根据openId,查询微信用户信息
            $userAuth = UserAuth::where("identity_type", "wechat")
                ->where("identifier", $openid)
                ->where("subject_id", $subject->id)->first();

            if ($userAuth) {
                if (!empty($type)) {
                    $query = $userAuth->user()
                        ->where("subject_id", $subject->id);

                    if ($register) {
                        switch ($type) {
                            case 'mobile':
                                if (Input::get("mobile", null)) {

                                    $user = $query->whereNotNull($type)
                                        ->where($type, Input::get("mobile"))
                                        ->first();
                                    if ($user) {
                                        throw new ResourceException("该".$type."已经被注册");
                                    }
                                }
                                break;
                            default:
                                throw new ResourceException("不支持该类型的注册:".$type);
                                break;
                        }
                    }

                    $user = $query->whereNotNull($type)
                        ->where($type, "!=", "")
                        ->first();
                } else {
                    $user = $userAuth->user;
                }

                if ($user) {
                    //用户存在处理用户微信信息更新
                    $wechatUserInfo = $this->getWechatUserInfo($subject->uuid, $openid);
                    //填充微信信息
                    $user->userProfile->update([
                        "wechat_nickname"  => $wechatUserInfo->nickname,
                        "wechat_avatar"    => $wechatUserInfo->avatar,
                        "wechat_province"  => $wechatUserInfo->province,
                        "wechat_city"      => $wechatUserInfo->city,
                        "wechat_country"   => $wechatUserInfo->country,
                        "wechat_sex"       => $wechatUserInfo->sex,
                        "wechat_language"  => $wechatUserInfo->language,
                        "wechat_privilege" => $wechatUserInfo->privilege,
                    ]);
                }

                return $user;
            } else {
                //userAuth 不存在,如果此时进入注册模式,还需判断手机号是否已经注册
                //在用户已经注册,换了微信使用的情况下会出现这种情况
                if ($register) {
                    $tempUser = User::where("subject_id", $subject->id)
                        ->where("mobile", Input::get("mobile"))
                        ->first();
                    if ($tempUser) {
                        throw new ResourceException("该".Input::get("identifier")."已经被注册");
                    }
                }

                return false;
            }
        } else {
            //todo 兼容企业号
            throw new PermissionDeniedException("暂不支持出微信以外场景");
        }
    }


    /**
     * 创建用户
     *
     * @param string $type ,mobile or email ..
     * @param        $info
     * @param string $requestType
     * @return $this|bool|\Illuminate\Database\Eloquent\Model|User
     */
    public function createUser($type = null, $info = null, $requestType = "")
    {
        $requestType = $requestType ?: Request::header("REQUEST-TYPE");

        if ($requestType == "WECHAT" || $requestType == 'bridge') {
            $openid = $this->getOpenid();
            $subject = AppUtils::getSubject();
            $uuid = $subject->uuid;

            if (!$this->existUser($type, true, $requestType)) {
                //用户不存在

                $wechatUserInfo = $this->getWechatUserInfo($uuid, $openid);

                $nickname = $wechatUserInfo->nickname;

                $data = [
                    "subject_id"     => $subject->id,
                    "nickname"       => $nickname,
                    "avatar"         => $wechatUserInfo->avatar,
                    "top_subject_id" => $subject->id,
                ];

                if (!empty($type)) {
                    $data = array_merge($data, [$type => Input::get('identifier')]);
                }

                DB::beginTransaction();

                //先判断有没有存在纯微信用户
                if ($this->existUser(null, true, $requestType)) {
                    //已经存在纯微信用户信息了,更新
                    $user = $this->existUser(null, true, $requestType);
                    $user->update($data);
                } else {
                    //不存在纯微信用户,直接创建
                    //创建用户
                    $user = User::create($data);

                    $user->userAuths()->create([
                        "identity_type" => "wechat",
                        "identifier"    => $openid,
                        "subject_id"    => $subject->id,

                    ]);

                    //填充微信信息
                    if ($user->userProfile) {
                        $user->userProfile->update([
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
                    }
                }

                DB::commit();

                return $user;
            } else {
                throw new UserExistException();
            }
        } else {
            throw new PermissionDeniedException("暂不支持来自非微信的注册");
        }
    }

    /**
     * 获取用户信息
     *
     * @param $userId
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function getUserInfo($userId)
    {
        $subjectId = AppUtils::getSubjectId();
        $user = User::where("subject_id", $subjectId)
            ->findOrFail($userId);

        if ($user->mobile) {
            $token = $user->createToken("easy", ["mobile-token"])->accessToken;
        } else {
            $token = $user->createToken("easy", ["wechat-token"])->accessToken;
        }

        $user->token = $token;

        return $user;
    }

    /**
     * 获取openid
     *
     * @return mixed
     */
    public function getOpenid()
    {
        try {
            $openid = Input::get("identifier");
            $openid = urldecode($openid);
            $openid = decrypt($openid);

            return $openid;
        } catch (DecryptException $e) {
            \Log::warning(Input::get("identifier"));
            \Log::error("openid解密失败");
            throw new ResourceException("openid无效");
        }
    }

    /**
     * @param $uuid
     * @param $openid
     * @return mixed
     */
    public function getWechatUserInfo($uuid, $openid)
    {
        //查询微信用户信息
        $wechatAuthInfo = WechatAuthInfo::where("uuid", $uuid)->first();
        if (!$wechatAuthInfo) {
            throw new PermissionDeniedException("公众号未授权");
        }

        $wechatUserInfo = WechatUserInfo::where("openid", $openid)
            ->where("app_id", $wechatAuthInfo->authorizer_appid)
            ->first();

        if (!$wechatUserInfo) {
            \Log::error("无法获取微信信息");

            throw new PermissionDeniedException("openid未找到,请在微信内打开");
        }

        return $wechatUserInfo;
    }
}
