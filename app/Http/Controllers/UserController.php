<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use App\Services\OrganizerDefaultLevelsService;

class UserController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', User::class);
        $query = User::query();
        if ($search = request('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
        }
        $query->when(request('role'), fn ($q, $role) => $q->where('role', $role));
        $query->when(request('status'), fn ($q, $status) => $q->where('status', $status));
        $users = $query->latest()->paginate(15)->withQueryString();
        return view('user.index', compact('users'));
    }

    public function create()
    {
        Gate::authorize('create', User::class);
        return view('user.create');
    }

    public function store(StoreUserRequest $request, OrganizerDefaultLevelsService $defaults)
    {
        Gate::authorize('create', User::class);
        $data = $request->validated();
        if (($data['status'] ?? null) === 'active') {
            $data['email_verified_at'] = now();
        }
        $user = User::create($data);
        if (($data['role'] ?? null) === 'User') $defaults->provisionFor($user);
        if (($data['status'] ?? null) === 'active') {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
        return redirect()->route('users.show', $user)->with('status', 'تم إنشاء المستخدم بنجاح.');
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);
        return view('user.show', compact('user'));
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);
        return view('user.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        Gate::authorize('update', $user);
        $data = $request->validated();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        // Activating a previously inactive managed account also completes its
        // email verification because the application has no separate workflow.
        $verifyOnActivation = ($data['status'] ?? null) === 'active'
            && $user->status !== 'active'
            && $user->email_verified_at === null;
        $user->update($data);
        if ($verifyOnActivation) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
        return redirect()->route('users.show', $user)->with('status', 'تم تحديث المستخدم بنجاح.');
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);
        $user->delete();
        return redirect()->route('users.index')->with('status', 'تم حذف المستخدم بنجاح.');
    }
}
