<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'Platform Admin'; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['Platform Admin', 'User'])],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return ['required' => 'حقل :attribute مطلوب.', 'email' => 'يرجى إدخال بريد إلكتروني صحيح.', 'unique' => ':attribute مستخدم بالفعل.', 'confirmed' => 'تأكيد كلمة المرور غير مطابق.', 'in' => 'قيمة :attribute غير صحيحة.', 'min' => 'يجب ألا يقل :attribute عن :min أحرف.'];
    }

    public function attributes(): array
    {
        return ['name' => 'الاسم', 'email' => 'البريد الإلكتروني', 'phone' => 'الهاتف', 'password' => 'كلمة المرور', 'role' => 'نوع المستخدم', 'status' => 'الحالة'];
    }
}
