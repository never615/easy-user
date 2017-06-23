<?php

namespace Mallto\User\Controller;


use Encore\Admin\Controllers\Base\AdminCommonController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Mallto\User\Data\User;


class UserController extends AdminCommonController
{


    /**
     * 获取这个模块的标题
     *
     * @return mixed
     */
    protected function getHeaderTitle()
    {
        return "用户管理";
    }

    /**
     * 获取这个模块的Model
     *
     * @return mixed
     */
    protected function getModel()
    {
        return User::class;
    }

    protected function gridOption(Grid $grid)
    {
        $grid->nickname();
        $grid->mobile();
        $grid->avatar()->image("", 50, 50);

        $grid->filter(function(Grid\Filter $filter){
            $filter->ilike("nickname");
            $filter->ilike("mobile");
        });

    }

    protected function formOption(Form $form)
    {
        $form->display("nickname");
        $form->display("avatar")->with(function ($value) {
            return "<img src='$value' style='height: 80px'/>";
        });
        $form->display("mobile");
        $form->display("email");
    }
}
