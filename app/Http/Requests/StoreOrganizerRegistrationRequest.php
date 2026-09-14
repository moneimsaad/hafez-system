<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class StoreOrganizerRegistrationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if ($this->filled('username')) {
            $this->merge(['username' => strtolower(trim((string) $this->input('username')))]);
        }
    }

    public function rules(): array
    {
        $pendingUser = $this->pendingRegistrationUser();
        $usernameRule = Rule::unique(User::class, 'username');

        if ($pendingUser) {
            $usernameRule->ignore($pendingUser);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'organization_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', $usernameRule],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->where(function ($query): void {
                    $query->where('status', '!=', 'inactive')
                        ->orWhereNotNull('email_verified_at')
                        ->orWhere('role', '!=', 'User');
                }),
            ],
            'phone' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    private function pendingRegistrationUser(): ?User
    {
        $email = strtolower(trim((string) $this->input('email')));

        if ($email === '') {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('role', 'User')
            ->where('status', 'inactive')
            ->whereNull('email_verified_at')
            ->first();
    }

    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'email' => 'يرجى إدخال بريد إلكتروني صحيح.',
            'unique' => ':attribute مستخدم بالفعل، اختر اسماً آخر.',
            'regex' => 'اسم المستخدم يجب أن يتكون من أحرف إنجليزية صغيرة وأرقام وشرطة سفلية فقط.',
            'confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'الاسم', 'organization_name' => 'اسم الجهة', 'username' => 'اسم المستخدم', 'email' => 'البريد الإلكتروني', 'phone' => 'الهاتف', 'password' => 'كلمة المرور'];
    }
}
