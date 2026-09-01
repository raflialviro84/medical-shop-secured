<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ResetUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:reset-password {identifier : email or username} {--password= : New password (default: password)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset a user password by email or username (sets to provided plain password).';

    public function handle()
    {
        $identifier = $this->argument('identifier');
        $new = $this->option('password') ?? 'password';

        $user = User::where('email', $identifier)->orWhere('username', $identifier)->first();

        if (! $user) {
            $this->error('User not found for: ' . $identifier);
            return 1;
        }

        $user->password = $new; // model will hash via cast
        $user->save();

        $this->info('Password reset for ' . $identifier . ' (new password: ' . $new . ')');
        return 0;
    }
}
