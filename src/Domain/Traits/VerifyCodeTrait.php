<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 11/07/2017
 * Time: 2:43 PM
 */

namespace Mallto\User\Domain\Traits;


use Illuminate\Support\Facades\Cache;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Utils\SubjectUtils;

trait VerifyCodeTrait
{

    /**
     * 检查验证码
     *
     * @param $verifyObj
     * @param $code
     * @return bool
     */
    protected function checkVerifyCode($verifyObj, $code)
    {
        $tempCode = Cache::get('code'.SubjectUtils::getSubjectId().$verifyObj);

        if ($tempCode != $code) {
            if (config("app.env") !== 'production' && $code == "000000") {
                return true;
            } else {
                throw  new ResourceException("验证码错误");
            }
        }

        return true;
    }
}