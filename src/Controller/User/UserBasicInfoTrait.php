<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\User;

use Illuminate\Support\Facades\DB;
use Mallto\Mall\Data\Member;
use Mallto\Mall\Domain\MemberOperationLogUsecase;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;

/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 2018/6/7
 * Time: 下午4:51
 */
trait UserBasicInfoTrait
{
    protected function basicInfoForm($form, $user)
    {
        $form->display('id', 'ID');

        $form->display("nickname");

        $form->display("avatar")->with(function ($value) {
            if ($value) {
                return "<img src='$value' style='height: 80px'/>";
            }
        });

        $form->display("mobile")->rules("mobile");


        //解绑微信
        $form->html('<a href="/admin/users/'.$this->currentId.'/unbind?type=wechat" class="btn btn-primary">解绑微信</a>');

        //解绑手机
        $form->html('<a href="/admin/users/'.$this->currentId.'/unbind?type=mobile" class="btn btn-primary">解绑手机</a>');

        $form->html('<h3>更换手机</h3>');
        $form->divider();
        //更换手机号
        $form->text("new_mobile", "新手机号");
        $form->buttonE("get_mobile_code", "获取验证码")
            ->on("click", function () use ($user) {

                $uuid = $user->subject->uuid;

                return <<<EOT
        var target = $(this).closest('.fields-group').find(".new_mobile");

        var loadIndex = layer.load(0, {shade: false}); //0代表加载的风格，支持0-2

        $.ajax({
            type: 'GET',
            url: '/api/code',
            dataType: "json",
            data: Object.assign({}, {iddd: Math.random()}, {'mobile': target.val(),'use':'reset'}),
            headers: {
                'REQUEST-TYPE': 'WEB',
                'Accept': 'application/json',
                'UUID': '{$uuid}'
            },
            success: function (data) {
                layer.close(loadIndex);
                toastr.success("请求成功");
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.close(loadIndex);
                errorHandler(XMLHttpRequest);
            }
        });
EOT;
            });
        $form->text("mobile_code", "验证码");
        $form->divider();

        $this->formSubject($form);
        $this->formAdminUser($form);
        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));
    }


    /**
     * 更换手机号
     *
     * @param $user
     * @param $form
     */
    private function updateMobile($form, $user)
    {
        $newMobile = request("new_mobile");
        $mobileCode = request("mobile_code");


        if ($newMobile && !$mobileCode) {
            throw new ResourceException("填写了新手机号,但是验证码为空");
        }

        if ($newMobile && $mobileCode) {
            //如果用户没有旧手机,则不允许更换
            if (!$form->model()->mobile) {
                throw new ResourceException("用户未绑定手机,无法更换");
            }

            $oldMobile = $form->model()->mobile;

            //校验验证码
            $this->smsUsecase->checkVerifyCode($newMobile, $mobileCode, 'reset',
                $user->subject->id);


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
     * @return bool
     */
    private function unbindMobile($user)
    {
        //检查用户是否有手机登录的方式,如果有则不能解绑手机
        $mobileAuth = $user->userAuths()
            ->where("identity_type", 'mobile')
            ->first();
        if ($mobileAuth) {
            throw new ResourceException("该用户使用手机+密码的方式登录,不支持解绑手机");
        }


        DB::beginTransaction();

        //解除会员表和用户表的关联
        Member::where("subject_id", $user->subject_id)
            ->where("mobile", $user->mobile)
            ->update([
                'user_id' => null,
            ]);

        //删除对应的会员表信息
//        Member::where("subject_id", $user->subject_id)
//            ->where("mobile", $user->mobile)
//            ->delete();


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
     * @return bool
     */
    private function unbindWechat($user)
    {

        $count = $user->userAuths()->count();
        if ($count <= 1) {
            throw new PermissionDeniedException("解绑失败,用户只有一种绑定方式,无法解绑");
        }

        $userAuth = $user->userAuths()
            ->where("identity_type", 'wechat')
            ->first();

        if (!$userAuth) {
            throw new PermissionDeniedException("解绑失败,用户未绑定微信");
        } else {
            $userAuth->delete();
        }


        return true;
    }

}