<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'users:make-admin
                            {email : Email of an existing account}
                            {--revoke : Remove admin access instead}';

    protected $description = 'Give an existing user access to the admin panel, or take it away';

    public function handle(): int
    {
        $user = User::where('email', strtolower($this->argument('email')))->first();

        if ($user === null) {
            $this->error('No user with that email. Register the account first.');

            return self::FAILURE;
        }

        // is_admin is not mass assignable on purpose, so it is set explicitly here.
        $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

        $this->info($user->is_admin
            ? "{$user->email} can now open /admin."
            : "{$user->email} can no longer open /admin.");

        return self::SUCCESS;
    }
}
