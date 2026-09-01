<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class RegisterForm extends Component
{
    public string $username = '';

    public string $full_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $address = '';

    public string $city = '';

    public string $contact = '';

    public function register()
    {
        $validated = $this->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'full_name' => $validated['full_name'] ?? null,
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'customer',
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'contact' => $validated['contact'] ?? null,
        ]);

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('home');
    }

    public function render()
    {
        return view('livewire.auth.register-form');
    }
}