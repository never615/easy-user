<?php
/**
 * Copyright (c) 2019. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Data\Repository;

use Mallto\User\Data\UserAuth;
use Mallto\User\Exceptions\UserAuthExistException;

/**
 * User: never615 <never615.com>
 * Date: 2019/7/12
 * Time: 12:06 PM
 */
class UserAuthRepository
{
    /**
     * @param $credentials
     * @param $user
     * @return mixed
     */
    public function create($credentials, $user)
    {
        $identityType = $credentials["identityType"];
        $identifier = $credentials['identifier'];
        $credential = $credentials['credential'] ?? null;

        //保存$credential的时候再进行一次加密
        $hashCreential = $credential ? \Hash::make($credential) : $credential;

        $exists = UserAuth::where([
            "identifier"     => $identifier,
            "identity_type"  => $identityType,
            'top_subject_id' => $user->top_subject_id,
            "subject_id"     => $user->subject_id,
            "user_id"        => $user->id,
            'credential'     => $credential ? $hashCreential : null,
        ])->exists();
        if (!$exists) {
            try {
                return UserAuth::create([
                    "identifier"    => $identifier,
                    "identity_type" => $identityType,
                    "subject_id"    => $user->subject_id,
                    "user_id"       => $user->id,
                    'credential'    => $credential ? $hashCreential : null,
                ]);
            } catch (\PDOException $e) {
                // Handle integrity violation SQLSTATE 23000 (or a subclass like 23505 in Postgres) for duplicate keys
                if (0 === strpos($e->getCode(), '23')) {
                    //检查如果已存在
                    \Log::error("创建userauth失败");
                    \Log::warning($e);
                    \Log::warning($credentials);
                    throw new UserAuthExistException();
                } else {
                    throw $e;
                }
            }
        } else {
            throw new UserAuthExistException();
        }
    }
}