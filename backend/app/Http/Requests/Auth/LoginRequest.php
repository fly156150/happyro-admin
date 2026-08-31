<?php

namespace App\Http\Requests\Auth;

use App\Data\Auth\ClientContext;
use App\Data\Auth\LoginData;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function loginData(): LoginData
    {
        return new LoginData(
            username: $this->string('username')->trim()->lower()->toString(),
            password: $this->string('password')->toString(),
            remember: $this->boolean('remember'),
        );
    }

    public function clientContext(): ClientContext
    {
        return new ClientContext($this->ip(), $this->userAgent());
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'username.required' => '请输入用户名。',
            'password.required' => '请输入密码。',
        ];
    }
}
