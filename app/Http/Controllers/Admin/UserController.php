<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::withCount('managedChannels')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $channels = Channel::orderBy('name')->get();
        return view('admin.users.create', compact('channels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,manager',
            'channels' => 'nullable|array',
            'channels.*' => 'exists:channels,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        if (!empty($validated['channels'])) {
            $user->managedChannels()->sync($validated['channels']);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User '{$user->name}' created successfully.");
    }

    public function edit(User $user): View
    {
        $channels = Channel::orderBy('name')->get();
        $user->load('managedChannels');

        return view('admin.users.edit', compact('user', 'channels'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,manager',
            'is_active' => 'nullable|boolean',
            'channels' => 'nullable|array',
            'channels.*' => 'exists:channels,id',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->is_active = $request->boolean('is_active', false);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $user->managedChannels()->sync($validated['channels'] ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User '{$user->name}' updated successfully.");
    }

    public function destroy(User $user): RedirectResponse
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User '{$name}' deleted successfully.");
    }
}
