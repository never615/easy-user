<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Listeners;

use Laravel\Passport\Events\RefreshTokenCreated;

class PruneOldTokens
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
     * @param  RefreshTokenCreated $event
     * @return void
     */
    public function handle(RefreshTokenCreated $event)
    {

    }
}
