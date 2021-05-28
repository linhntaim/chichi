<?php

namespace App\Console\Commands;

use App\ModelRepositories\AdminRepository;
use App\Utils\ClientSettings\Traits\AdminConsoleClientTrait;

class VerifyAdminEmailCommand extends VerifyEmailCommand
{
    use AdminConsoleClientTrait;

    protected $signature = 'verify:email:admin {--code=} {--user=} {--unverified}';

    protected function getUserRepositoryClass()
    {
        return AdminRepository::class;
    }
}