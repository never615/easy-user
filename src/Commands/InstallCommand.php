<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Commands;

use Illuminate\Console\Command;
use Mallto\User\Seeder\TablesSeeder;

class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the easy-user package';

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
    public function handle()
    {
        $this->publishDatabase();

    }

    /**
     * Create tables and seed it.
     *
     * @return void
     */
    public function publishDatabase()
    {
        $this->call('migrate', ['--path' => str_replace(base_path(), '', __DIR__).'/../../migrations/']);

        $this->call('db:seed', ['--class' => TablesSeeder::class]);
    }
}
