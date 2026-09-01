<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class IndexPage extends Component
{
    use WithPagination;

    public string $role = '';

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function delete(int $userId): void
    {
        if ($userId === Auth::id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        User::findOrFail($userId)->delete();

        session()->flash('success', 'User deleted successfully.');
    }

    public function render()
    {
        $users = User::query()
            ->when($this->role !== '', function ($query) {
                $query->where('role', $this->role);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.users.index-page', compact('users'));
    }
}