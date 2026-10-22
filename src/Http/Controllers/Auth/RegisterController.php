<?php

namespace Parth\LaravelRbacSuite\Http\Controllers\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Parth\LaravelRbacSuite\Http\Controllers\BaseController;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\PasswordRule;
use Parth\LaravelRbacSuite\Models\Role;

class RegisterController extends BaseController
{
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     */
    public function redirectTo(): string
    {
        $homeRoute = config('rbac-suite.routes.home', 'home');
        if (Route::has($homeRoute)) {
            return route($homeRoute);
        }

        return '/';
    }

    protected function getUserModelClass(): string
    {
        return config('rbac-suite.user_model', 'App\\Models\\User');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware(function (Request $request, $next) {
            if (! AuthSetting::isRegistrationEnabled()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Registration is currently disabled.',
                    ], 403);
                }

                return redirect()->route('login')->with('error', 'Registration is currently disabled.');
            }

            return $next($request);
        });
    }

    /**
     * Show the application registration form.
     */
    public function showRegistrationForm(): View|RedirectResponse
    {
        if (! AuthSetting::isRegistrationEnabled()) {
            return redirect()->route('login')->with('error', 'Registration is currently disabled.');
        }

        $view = view()->exists('rbac-suite::auth.register')
            ? 'rbac-suite::auth.register'
            : 'auth.register';

        return view($view);
    }

    /**
     * Handle a registration request for the application.
     */
    public function register(Request $request): RedirectResponse|JsonResponse
    {
        if (! AuthSetting::isRegistrationEnabled()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Registration is currently disabled.',
                ], 403);
            }

            return redirect()->route('login')->with('error', 'Registration is currently disabled.');
        }

        $input = $request->all();
        if (isset($input['email']) && is_string($input['email'])) {
            $input['email'] = strtolower(trim($input['email']));
        }
        if (isset($input['mobile_number']) && is_string($input['mobile_number'])) {
            $input['mobile_number'] = trim($input['mobile_number']);
        }
        if (isset($input['name']) && is_string($input['name'])) {
            $input['name'] = trim($input['name']);
        }

        $this->validator($input)->validate();

        try {
            $user = $this->create($input);
            event(new Registered($user));
        } catch (QueryException $e) {
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'users_email_unique')) {
                throw ValidationException::withMessages([
                    'email' => ['This email address is already registered.'],
                ]);
            }

            throw $e;
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', $e->getMessage())
                ->withErrors(['role' => $e->getMessage()]);
        }

        $this->guard()->login($user);

        if ($response = $this->registered($request, $user)) {
            return $response;
        }

        return $request->wantsJson()
                    ? new JsonResponse(['message' => 'Registration successful!'], 201)
                    : redirect($this->redirectPath())->with('status', 'Registration successful!');
    }

    /**
     * The user has been registered.
     *
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        if ($request->wantsJson()) {
            return new JsonResponse([
                'message' => 'Registration successful!',
                'user' => $user,
            ], 201);
        }

        return redirect($this->redirectPath())->with('status', 'Registration successful!');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array<string, mixed>  $data
     */
    protected function validator(array $data): ValidatorContract
    {
        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }
        if (isset($data['mobile_number']) && is_string($data['mobile_number'])) {
            $data['mobile_number'] = trim($data['mobile_number']);
        }
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = trim($data['name']);
        }

        $userModel = $this->getUserModelClass();

        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function ($attribute, $value, $fail) use ($userModel) {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }
                    $normalized = strtolower(trim($value));
                    if ($userModel::whereRaw('LOWER(email) = ?', [$normalized])->exists()) {
                        $fail('This email address is already registered.');
                    }
                },
            ],
            'mobile_number' => [
                'required',
                'string',
                'regex:/^(?:\+91)?[6-9]\d{9}$/',
                function ($attribute, $value, $fail) use ($userModel) {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }
                    $trimmed = trim($value);
                    $digits = preg_replace('/^\+91/', '', $trimmed);
                    $withPrefix = '+91'.$digits;

                    if ($userModel::whereIn('mobile_number', [$trimmed, $digits, $withPrefix])->exists()) {
                        $fail('This mobile number is already registered.');
                    }
                },
            ],
            'password' => PasswordRule::getValidationRules(true),
        ], [
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'mobile_number.required' => 'The mobile number field is required.',
            'mobile_number.regex' => 'Please enter a valid 10-digit mobile number.',
            'mobile_number.unique' => 'This mobile number is already registered.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array<string, mixed>  $data
     */
    protected function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $defaultRoleName = config('rbac-suite.roles.default_user_role', 'User');
            $role = Role::where('name', $defaultRoleName)->first()
                ?? Role::where('name', 'user')->first()
                ?? Role::where('name', 'User')->first();

            if (! $role) {
                throw new \RuntimeException('Default user role is not configured.');
            }

            $userModel = $this->getUserModelClass();
            $user = $userModel::create([
                'name' => trim((string) $data['name']),
                'email' => strtolower(trim((string) $data['email'])),
                'mobile_number' => trim((string) $data['mobile_number']),
                'password' => Hash::make($data['password']),
                'is_active' => '1',
            ]);

            if (method_exists($user, 'assignRole')) {
                $user->assignRole($role);
            }

            return $user;
        });
    }
}
