<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\User;

use Illuminate\Support\Facades\DB;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Utils\AppUtils;

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
        $form->displayE('id', 'ID');

        $form->displayE("nickname");

        $form->displayE("avatar")->with(function ($value) {
            if ($value) {
                return "<img src='$value' style='height: 80px'/>";
            }
        });

        $form->displayE("mobile")->rules("mobile");


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
        $form->displayE('created_at', trans('admin.created_at'));
        $form->displayE('updated_at', trans('admin.updated_at'));
    }




}
