<?php

namespace App\Console\Commands;

use App\Console\Commands\Service\CreateMigration;
use App\Console\Commands\Service\CreateModel;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Auth\User;

/**
 * Class CreateApiFromConfigTable
 * @package App\Console\Commands
 *
 * @property User $user
 */

class CreateApiFromConfigTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:create {config?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command create api class';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $userClass = config('auth.providers.users.model');
        $this->user = new $userClass();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        $table = $this->argument('config');

        (new CreateMigration($this->user))->handle($table);
        (new CreateModel($this->user))->handle($table);

        return 0;
    }
}
