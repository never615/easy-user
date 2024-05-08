<?php
/**
 * Copyright (c) 2019. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Data\Repository;

use Mallto\User\Data\UserAuth;
use Mallto\User\Exceptions\UserAuthExistException;
use Illuminate\Support\Facades\Log;

/**
 * User: never615 <never615.com>
 * Date: 2019/7/12
 * Time: 12:06 PM
 */
class UserAuthRepository implements UserAuthRepositoryInterface
{

    /**
     * @param      $credentials
     * @param      $user
     * @param null $subject
     *
     * @return mixed
     */
    public function create($credentials, $user, $subject = null)
    {
        $identityType = $credentials["identityType"];
        $identifier = $credentials['identifier'];
        $credential = $credentials['credential'] ?? null;

        //保存$credential的时候再进行一次加密
        $hashCreential = $credential ? \Hash::make($credential) : $credential;

//        $exists = UserAuth::where([
//            "identifier"    => $identifier,
//            "identity_type" => $identityType,
//            "subject_id"    => $user->subject_id,
//            "user_id"       => $user->id,
//            'credential'    => $credential ? $hashCreential : null,
//        ])->exists();
//        if ( ! $exists) {
        try {
            return UserAuth::updateOrCreate([
                "identifier"    => $identifier,
                "identity_type" => $identityType,
                "subject_id"    => $user->subject_id,
                "user_id"       => $user->id,
            ], [
                'credential' => $credential ? $hashCreential : null,
            ]);
        } catch (\PDOException $e) {
            // Handle integrity violation SQLSTATE 23000 (or a subclass like 23505 in Postgres) for duplicate keys
            if (0 === strpos($e->getCode(), '23505')) {
                //检查如果已存在
                //Log::warning("用户授权方式已存在1:" . $user->id);
                //Log::warning($e);
                //Log::warning(new \Exception());
                //Log::warning($credentials);
                //throw new UserAuthExistException($user->id);

                //事务中发生了该异常,则必须先结束事务,才能进行其他数据库操作
                //if (DB::transactionLevel() > 0) {
                //    DB::rollBack();
                //}
                //
                //$userAuth = UserAuth::where([
                //    "identifier"    => $identifier,
                //    "identity_type" => $identityType,
                //    "subject_id"    => $user->subject_id,
                //    'credential'    => $credential ? $hashCreential : null,
                //])->first();
                //if ($userAuth) {
                //    Log::debug($userAuth);
                //
                //    return $userAuth;
                //}
                throw new UserAuthExistException();
            } else {
                throw $e;
            }
        }
//        } else {
////            Log::warning("用户授权方式已存在2:".$user->id);
////            Log::warning($credentials);
////            Log::warning((new \Exception()));
//        }
    }
}
