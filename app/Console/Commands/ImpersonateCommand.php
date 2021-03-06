<?php

/**
 * Base - Any modification needs to be approved, except the space inside the block of TODO
 */

namespace App\Console\Commands;

use App\Console\Commands\Base\Command;
use App\ModelRepositories\AdminRepository;
use App\ModelRepositories\ImpersonateRepository;
use App\ModelRepositories\UserRepository;
use App\Models\Admin;
use App\Models\Impersonate;
use App\Models\User;
use Throwable;

class ImpersonateCommand extends Command
{
    protected $signature = 'impersonate {user} {admin_id}';

    protected function go()
    {
        if (($user = $this->getUser()) && ($admin = $this->getAdmin())) {
            try {
                $impersonate = $this->createImpersonate($user, $admin);
                $this->warn(json_encode([
                    'impersonate_token' => $impersonate->impersonate_token,
                ]));
            }
            catch (Throwable $exception) {
                return;
            }
        }
    }

    private function getAdmin()
    {
        $admin = (new AdminRepository())->notStrict()
            ->getById($this->argument('admin_id'));
        return $admin && $admin->hasPermission('impersonate') ? $admin : null;
    }

    /**
     * @return User
     */
    private function getUser()
    {
        return (new UserRepository())->notStrict()
            ->getUniquely($this->argument('user'));
    }

    /**
     * @param User $user
     * @param Admin $admin
     * @return Impersonate
     * @throws
     */
    private function createImpersonate(User $user, Admin $admin)
    {
        return (new ImpersonateRepository())->createWithAttributes([
            'user_id' => $user->id,
            'via_user_id' => $admin->user_id,
        ]);
    }
}
