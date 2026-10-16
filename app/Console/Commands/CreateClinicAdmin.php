<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateClinicAdmin extends Command
{
    protected $signature = 'clinic:create-admin {email} {--name=مدير عيادات هلال}';

    protected $description = 'إنشاء مدير الموقع دون كلمة مرور افتراضية';

    public function handle(): int
    {
        $password = $this->secret('كلمة المرور (12 حرفًا على الأقل)');
        $data = ['email' => $this->argument('email'), 'password' => $password];
        $validator = Validator::make($data, ['email' => 'required|email|unique:users,email', 'password' => 'required|string|min:12']);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        Role::findOrCreate('super_admin', 'web');
        $user = User::create($data + ['name' => $this->option('name'), 'role' => 'admin']);
        $user->assignRole('super_admin');
        $this->info('تم إنشاء مدير الموقع.');

        return self::SUCCESS;
    }
}
