<?php

namespace Mallto\User\Commands;

use Illuminate\Console\Command;
use Mallto\User\Seeder\UserTablesSeeder;

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
    public function fire()
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

        $this->call('db:seed', ['--class' => UserTablesSeeder::class]);
    }
}
