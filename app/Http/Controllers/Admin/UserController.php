<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id', 'name', 'email', 'created_at', 'updated_at'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $users = User::query()
            ->select(['id', 'name', 'email', 'is_admin', 'created_at', 'updated_at'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = trim((string) $request->query('q'));
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->query('admin') === 'yes', fn ($query) => $query->where('is_admin', true))
            ->when($request->query('admin') === 'no', fn ($query) => $query->where('is_admin', false))
            ->orderBy($sort, $direction)
            ->when($sort !== 'id', fn ($query) => $query->orderBy('id', $direction))
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(User $user): View
    {
        $user->loadCount('books');

        return view('admin.users.show', ['managedUser' => $user]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['managedUser' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $oldValues = $user->only(['name', 'email']);
        $validated = $request->validated();

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ])->save();

        $newValues = $user->only(['name', 'email']);

        if ($oldValues !== $newValues) {
            $this->audit($request, 'user.updated', $user, $oldValues, $newValues);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User updated successfully.');
    }

    public function promote(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('status', 'User is already an administrator.');
        }

        $oldValues = ['is_admin' => false];
        $user->forceFill(['is_admin' => true])->save();
        $this->audit($request, 'user.promoted_to_admin', $user, $oldValues, ['is_admin' => true]);

        return back()->with('status', 'Administrator rights granted.');
    }

    public function demote(Request $request, User $user): RedirectResponse
    {
        if (! $user->isAdmin()) {
            return back()->with('status', 'User is not an administrator.');
        }

        if (User::where('is_admin', true)->count() <= 1) {
            return back()->withErrors([
                'admin' => 'You cannot remove the last administrator.',
            ]);
        }

        $oldValues = ['is_admin' => true];
        $user->forceFill(['is_admin' => false])->save();
        $this->audit($request, 'user.demoted_from_admin', $user, $oldValues, ['is_admin' => false]);

        return back()->with('status', 'Administrator rights removed.');
    }

    private function audit(Request $request, string $action, User $entity, array $oldValues, array $newValues): void
    {
        AdminAuditLog::create([
            'admin_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => User::class,
            'entity_id' => $entity->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
