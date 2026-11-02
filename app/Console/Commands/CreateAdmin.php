<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create an administrator using an interactive, hidden password prompt';

    public function handle(): int
    {
        $data = [
            'name' => $this->ask('Name'),
            'email' => strtolower(trim($this->ask('Email') ?? '')),
            'password' => $this->secret('Password (at least 12 characters, mixed case and numbers)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->role = 'admin';
        $user->save();
        $this->info('Administrator created. Sign in at /admin/login.');

        return self::SUCCESS;
    }
}
