<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class EditForm extends Component
{
    public User $user;

    public string $username = '';

    public string $email = '';

    public string $full_name = '';

    public string $role = 'customer';

    public string $address = '';

    public string $city = '';

    public string $contact = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->full_name = (string) $user->full_name;
        $this->role = $user->role;
        $this->address = (string) $user->address;
        $this->city = (string) $user->city;
        $this->contact = (string) $user->contact;
    }

    public function save()
    {
        $validated = $this->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'full_name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:admin,customer'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'confirmed'],
        ]);

        $payload = [
            'username' => $validated['username'],
            'email' => $validated['email'],
            'full_name' => $validated['full_name'] ?? null,
            'role' => $validated['role'],
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'contact' => $validated['contact'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $this->user->update($payload);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function delete()
    {
        if ($this->user->id === Auth::id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $this->user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function render()
    {
        return view('livewire.admin.users.edit-form');
    }
}