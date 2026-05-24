<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Console\Commands;

use App\Enums\GenderEnum;
use App\Models\Site;
use App\Models\User;
use App\Support\VisitorPortalPermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateVisitorPortalAdmin extends Command
{
    protected $signature = 'visitorportal:create-admin
        {--email= : Admin e-mail address}
        {--first-name= : Admin first name}
        {--last-name= : Admin last name}
        {--site-id= : Primary site ID}
        {--force : Update the existing user if the e-mail already exists}';

    protected $description = 'Create a production-safe initial admin without demo data or default passwords.';

    public function handle(): int
    {
        VisitorPortalPermissions::sync($this);

        $site = $this->resolveSite();

        if (! $site) {
            return self::FAILURE;
        }

        $email = $this->promptEmail();
        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser && ! $this->option('force')) {
            $this->error('A user with this e-mail already exists. Re-run with --force to assign the admin role and update profile fields.');

            return self::FAILURE;
        }

        if ($existingUser && $this->option('force') && ! $this->confirm('This will update the existing user password, profile fields, site assignment and admin role. Continue?', false)) {
            $this->warn('No changes were made.');

            return self::FAILURE;
        }

        $firstName = $this->promptRequiredString('first-name', 'First name');
        $lastName = $this->promptRequiredString('last-name', 'Last name');
        $password = $this->promptPassword();

        $user = $existingUser ?? new User;
        $user->forceFill([
            'site_id' => $site->id,
            'first_name' => $firstName,
            'name' => $lastName,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'gender' => $user->gender ?? GenderEnum::Not_Specified,
            'is_active' => true,
            'deactivated_at' => null,
        ])->save();

        $user->assignRole('admin');

        $this->info("Admin user {$email} is ready.");

        return self::SUCCESS;
    }

    private function resolveSite(): ?Site
    {
        $siteId = $this->option('site-id');

        if ($siteId !== null && $siteId !== '') {
            $site = Site::query()->active()->find((int) $siteId);

            if (! $site) {
                $this->error('The given site ID does not exist or is inactive.');

                return null;
            }

            return $site;
        }

        return Site::default();
    }

    private function promptEmail(): string
    {
        $email = trim((string) ($this->option('email') ?: $this->ask('Admin e-mail address')));
        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));
            exit(self::FAILURE);
        }

        return $email;
    }

    private function promptRequiredString(string $option, string $label): string
    {
        $value = trim((string) ($this->option($option) ?: $this->ask($label)));
        $validator = Validator::make([$option => $value], [
            $option => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first($option));
            exit(self::FAILURE);
        }

        return $value;
    }

    private function promptPassword(): string
    {
        $password = (string) $this->secret('Admin password');
        $confirmation = (string) $this->secret('Confirm admin password');

        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'password' => ['required', 'confirmed', 'min:12', Rule::notIn(['password', 'ChangeMe-42!', 'changeme-42!'])],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));
            exit(self::FAILURE);
        }

        return $password;
    }
}
