<?php
/**
 * Copyright (c) 2019. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Data\Repository;

/**
 * User: never615 <never615.com>
 * Date: 2019/7/12
 * Time: 12:06 PM
 */
interface UserAuthRepositoryInterface
{
    /**
     * @param $credentials
     * @param $user
     * @return mixed
     */
    public function create($credentials, $user);

}