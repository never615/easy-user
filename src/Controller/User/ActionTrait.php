<?php
/**
 * Copyright (c) 2019. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\User;

use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\MessageBag;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Data\User;
use Mallto\User\Data\UserProfile;

/**
 * User: never615 <never615.com>
 * Date: 2019/12/2
 * Time: 12:17 下午
 */
trait ActionTrait
{

    /**
     * 解绑
     *
     * 解绑手机和解绑微信
     *
     * @param $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unbind($id)
    {
        $type = Request::input("type");

        $user = User::findOrFail($id);

        $count = $user->userAuths()->count();
        if ($count <= 1) {
            throw new PermissionDeniedException("解绑失败,用户只有一种绑定方式,无法继续解绑");
        }

        switch ($type) {
            case 'wechat':
                $result = $this->unbindWechat($user);
                break;
            case 'mobile':
                $result = $this->unbindMobile($user);
                break;
            default:
                throw new PermissionDeniedException("无效的请求参数");

                break;
        }

        //记录操作人
        $admin = Admin::user();
        $user->admin_user_id = $admin->id;
        $user->save();

        if ($result) {
            $success = new MessageBag([
                'title' => "解绑成功",
            ]);

            return back()->with(compact('success'));
        } else {
            throw new ResourceException("解绑失败");
        }
    }


    /**
     * 更换手机号
     *
     * @param $user
     * @param $form
     */
    protected function updateMobile($form, $user)
    {
        $newMobile = request("new_mobile");
        $mobileCode = request("mobile_code");

        if ($newMobile) {
            //如果用户没有旧手机,则不允许更换
            if ( ! $form->model()->mobile) {
                throw new ResourceException("用户未绑定手机,无法更换");
            }
//            $oldMobile = $form->model()->mobile;

            if ( ! AppUtils::isTestEnv()) {
                //校验验证码
                $this->smsUsecase->checkVerifyCode($newMobile, $mobileCode, 'reset',
                    $user->subject->id);
            }

            //更新user auth的sms方式
            $user->userAuths()->where("identity_type", "sms")
                ->update([
                    "identifier" => $newMobile,
                ]);
        }
    }


    /**
     * 解绑手机
     *
     * @param $user
     *
     * @return bool
     */
    protected function unbindMobile($user)
    {
        if (empty($user->mobile)) {
            throw new PermissionDeniedException("解绑失败,用户未绑定手机");
        }

        DB::beginTransaction();
        $user->mobile = null;
        $user->save();

        //更新user auth的sms方式
        $user->userAuths()
            ->where("identity_type", "sms")
            ->orWhere("identity_type", "mobile")
            ->delete();

        DB::commit();

        return true;
    }


    /**
     * 解绑微信
     *
     * @param $user
     *
     * @return bool
     */
    protected function unbindWechat($user)
    {
        $userAuth = $user->userAuths()
            ->where("identity_type", 'wechat')
            ->first();

        if ( ! $userAuth) {
            throw new PermissionDeniedException("解绑失败,用户未绑定微信");
        }

        if ($userAuth->delete()) {
            $userProfile = UserProfile::where('user_id', $userAuth->user_id)->first();
            $userProfile->wechat_user = null;
            $userProfile->save();
        }

        return true;
    }
}
