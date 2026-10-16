<?php

namespace Modules\PatientCore\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Nwidart\Modules\Facades\Module;

class PatientAuth extends Component
{
    public $type = 'signup'; // signup or login

    public $name;

    public $phone;

    public $password;

    public $password_confirmation;

    protected $queryString = ['type'];

    public function mount()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
    }

    protected function rules()
    {
        if ($this->type === 'signup') {
            return [
                'name' => 'required|string|max:255',
                'phone' => ['required', 'string', 'unique:users,phone', 'regex:/^(010|011|012|015)[0-9]{8}$/'],
                'password' => 'required|string|min:8|confirmed',
            ];
        }

        return [
            'phone' => ['required', 'string', 'regex:/^(010|011|012|015)[0-9]{8}$/'],
            'password' => 'required|string',
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    protected $messages = [
        'phone.regex' => 'يرجى إدخال رقم واتساب مصري صحيح (مثال: 01012345678)',
        'phone.unique' => 'هذا الرقم مسجل بالفعل، يرجى تسجيل الدخول.',
        'password.min' => 'كلمة المرور يجب أن لا تقل عن 8 أحرف.',
        'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
    ];

    public function signup()
    {
        abort_unless(Module::find('PatientCore')?->isEnabled(), 404);
        $key = 'patient-auth:'.request()->ip();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'role' => 'patient',
        ]);

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('home');
    }

    public function login()
    {
        abort_unless(Module::find('PatientCore')?->isEnabled(), 404);
        $key = 'patient-auth:'.request()->ip();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);
        $this->validate();

        if (Auth::attempt(['phone' => $this->phone, 'password' => $this->password, 'role' => 'patient'])) {
            request()->session()->regenerate();

            return redirect()->route('home');
        }

        $this->addError('phone', 'بيانات الاعتماد هذه غير متطابقة مع سجلاتنا.');
    }

    public function toggleType()
    {
        $this->type = $this->type === 'signup' ? 'login' : 'signup';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.patient-auth')
            ->layout('layouts.guest');
    }
}
