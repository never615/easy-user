<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Commands;

use Illuminate\Console\Command;

class UpdateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the easy-user package';

    /**
     * Install directory.
     *
     * @var string
     */
    protected $directory = '';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
//        $this->call('migrate', ['--path' => str_replace(base_path(), '', __DIR__).'/../../migrations/']);
    }

}
