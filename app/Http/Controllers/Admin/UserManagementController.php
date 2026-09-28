<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function __construct(private readonly UserManagementService $users) {}

    public function index(): View
    {
        return view('admin.users.index', ['users' => $this->users->listUsers()]);
    }

    public function create(): View
    {
        return view('admin.users.create', ['roles' => UserRole::cases()]);
    }

    public function store(Request $request): RedirectResponse|Response
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,field_staff'],
            'whatsapp_number' => ['nullable', 'required_if:role,field_staff', 'string', 'regex:/^\\+[1-9][0-9]{7,14}$/'],
        ]);

        $user = $this->users->createUser($data['name'], $data['email'], $data['password'], UserRole::from($data['role']));

        if ($user->role === UserRole::FieldStaff && isset($data['whatsapp_number'])) {
            $message = implode("\n", [
                'Hello '.$user->name.', your Prottyashi SFP staff account has been created.',
                'Username: '.$user->email,
                'Password: '.$data['password'],
                'Sign in: https://thetakibkhan.alwaysdata.net',
                'Please keep these login details private.',
            ]);
            $phoneNumber = ltrim($data['whatsapp_number'], '+');

            return response()->view('admin.users.created', [
                'staffName' => $user->name,
                'whatsappUrl' => 'https://wa.me/'.$phoneNumber.'?text='.rawurlencode($message),
            ])->header('Cache-Control', 'no-store, private');
        }

        return to_route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user, 'roles' => UserRole::cases()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:admin,field_staff'],
        ]);

        $this->users->updateUser($user, $data['name'], $data['email'], UserRole::from($data['role']));

        return to_route('admin.users.index')->with('status', 'User updated.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->users->deactivate($user);

        return to_route('admin.users.index')->with('status', 'User deactivated.');
    }
}
