<?php

namespace App\Console\Commands;

use App\Contracts\Auth\UserProvisioner;
use App\Data\Auth\CreateUserData;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateUserCommand extends Command
{
    protected $signature = 'gm:user:create
        {username? : 登录用户名}
        {--name= : 显示名称}
        {--password= : 登录密码；省略时安全提示输入}
        {--role=super_admin : super_admin、administrator、operator 或 auditor}
        {--ensure : 已存在时更新密码、名称和角色}
        {--no-color : 禁用 ANSI 颜色}';

    protected $description = 'Create an internal GM user';

    public function __construct(private readonly UserProvisioner $users)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->argument('username')) {
            $this->renderUsage();

            return self::SUCCESS;
        }

        $password = $this->option('password') ?: $this->secret('请输入密码');
        $input = [
            'username' => mb_strtolower(trim((string) $this->argument('username'))),
            'name' => trim((string) ($this->option('name') ?: $this->argument('username'))),
            'password' => $password,
            'role' => $this->option('role'),
        ];
        $existing = User::query()->where('username', $input['username'])->first();
        $validator = Validator::make($input, [
            'username' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_.-]+$/', Rule::unique('users')->ignore($existing?->id)],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:5', 'max:255'],
            'role' => ['required', Rule::in(['super_admin', 'administrator', 'operator', 'auditor'])],
        ]);

        if ($validator->fails()) {
            $this->newLine();
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            $this->newLine();

            return self::FAILURE;
        }

        if ($existing && ! $this->option('ensure')) {
            $this->error("用户 {$existing->username} 已存在；使用 --ensure 更新");

            return self::FAILURE;
        }

        if ($existing) {
            $data = $validator->validated();
            $existing->update(['name' => $data['name'], 'password' => $data['password'], 'is_active' => true]);
            $existing->roles()->sync([
                Role::query()->firstOrCreate(['name' => $data['role']], ['label' => $this->roleLabel($data['role'])])->id,
            ]);
            $user = $existing;
        } else {
            $user = $this->users->create(new CreateUserData(...$validator->validated()));
        }

        $this->newLine();
        $this->info("已创建 GM 用户 {$user->username}");
        $this->newLine();

        return self::SUCCESS;
    }

    private function renderUsage(): void
    {
        $color = ! $this->option('no-color');
        $style = fn (string $code, string $text): string => $color ? "\033[{$code}m{$text}\033[0m" : $text;

        $this->output->writeln('');
        $this->output->writeln($style('1;36', 'HappyRO GM 用户管理'));
        $this->output->writeln('');
        $this->output->writeln($style('1;33', '用法'));
        $this->output->writeln('  '.$style('1;32', 'php artisan gm:user:create').' <username> [选项]');
        $this->output->writeln('');
        $this->output->writeln($style('1;33', '常用例子'));
        $this->output->writeln($style('36', '  php artisan gm:user:create admin --name="管理员"'));
        $this->output->writeln($style('36', '  php artisan gm:user:create auditor --role=auditor --no-color'));
        $this->output->writeln('');
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'super_admin' => '超级管理员',
            'administrator' => '管理员',
            'operator' => '运营人员',
            'auditor' => '审计员',
        };
    }
}
