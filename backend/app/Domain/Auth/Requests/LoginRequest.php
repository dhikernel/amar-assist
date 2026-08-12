<?php

declare(strict_types=1);

namespace App\Domain\Auth\Requests;

use App\Domain\Auth\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_TENTATIVAS = 5;

    private const BLOQUEIO_SEGUNDOS = 60;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc,strict', 'max:150'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe a senha.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function autenticar(): User
    {
        $this->garantirQueNaoEstaBloqueado();

        if (! Auth::validate($this->only('email', 'password'))) {
            RateLimiter::hit($this->chaveDeBloqueio(), self::BLOQUEIO_SEGUNDOS);

            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas não conferem.'],
            ]);
        }

        RateLimiter::clear($this->chaveDeBloqueio());

        return Auth::getProvider()->retrieveByCredentials($this->only('email'));
    }

    protected function garantirQueNaoEstaBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->chaveDeBloqueio(), self::MAX_TENTATIVAS)) {
            return;
        }

        Event::dispatch(new Lockout($this));

        $segundos = RateLimiter::availableIn($this->chaveDeBloqueio());

        throw ValidationException::withMessages([
            'email' => [sprintf(
                'Tentativas de acesso em excesso. Tente novamente em %d segundos.',
                $segundos
            )],
        ])->status(429);
    }

    protected function chaveDeBloqueio(): string
    {
        return 'login:'.Str::transliterate((string) $this->input('email')).'|'.$this->ip();
    }
}
