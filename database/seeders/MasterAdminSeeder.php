<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MasterAdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('MASTER_ADMIN_NAME', 'Jameel Admin');
        $email = env('MASTER_ADMIN_EMAIL', 'admin@example.com');
        $password = env('MASTER_ADMIN_PASSWORD', 'ChangeMeNow123!');
        $isAdmin = true;

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ],
        );

        if (property_exists($user, 'is_admin') || \Schema::hasColumn($user->getTable(), 'is_admin')) {
            $user->forceFill([
                'is_admin' => $isAdmin,
            ])->save();
        }

        $this->command?->info("Master admin ensured: {$email}");
    }
}
