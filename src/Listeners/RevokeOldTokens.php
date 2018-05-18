<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Listeners;


use Carbon\Carbon;
use Laravel\Passport\Events\AccessTokenCreated;

/**
 * 颁发令牌
 * Class RevokeOldTokens
 *
 * @package Mallto\User\Listeners
 */
class RevokeOldTokens
{
    /**
     * Create the event listener.
     *
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AccessTokenCreated $event
     * @return void
     */
    public function handle(AccessTokenCreated $event)
    {

        $userId = $event->userId;
        $clientId = $event->clientId;
        $tokenId = $event->tokenId;

        \DB::table("oauth_access_tokens")
            ->where("id", "!=", $tokenId)
            ->where("user_id", $userId)
            ->where("client_id", $clientId)
            ->where("created_at", "<", Carbon::now()->addDays(-15))
            ->delete();


    }
}
