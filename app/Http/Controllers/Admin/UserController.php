<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
    ) {}

    /**
     * List and search platform users.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $role = (string) $request->query('role', '');

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, AccountStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when(in_array($role, UserRole::values(), true), fn ($query) => $query->where('role', $role))
            ->withCount('monitoredTopics')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'status' => $status,
            'role' => $role,
            'statuses' => AccountStatus::cases(),
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Show a single user with their activity counters.
     */
    public function show(User $user): View
    {
        $user->loadCount(['monitoredTopics', 'newsItems', 'notificationDeliveries', 'pushSubscriptions']);

        return view('admin.users.show', [
            'user' => $user,
        ]);
    }

    /**
     * Suspend a user account. Admins cannot suspend themselves or other admins.
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->assertCanSuspend($request->user(), $user);

        if (! $user->isSuspended()) {
            $user->suspend();
        }

        $this->audit->record('user.suspended', $user);

        return Redirect::route('admin.users.show', $user)
            ->with('status', 'user-suspended');
    }

    /**
     * Reactivate a suspended user account.
     */
    public function reactivate(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() && ! $request->user()->is($user), 403);

        if (! $user->isActive()) {
            $user->reactivate();
        }

        $this->audit->record('user.reactivated', $user);

        return Redirect::route('admin.users.show', $user)
            ->with('status', 'user-reactivated');
    }

    /**
     * The Gate bypass for admins means the policy alone cannot protect these
     * rules, so they are enforced explicitly here.
     */
    protected function assertCanSuspend(User $actor, User $target): void
    {
        abort_unless(
            $actor->isAdmin() && ! $target->isAdmin() && ! $actor->is($target),
            403,
        );
    }
}
