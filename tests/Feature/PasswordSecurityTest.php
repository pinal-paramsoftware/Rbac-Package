<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Parth\LaravelRbacSuite\Models\PasswordRule;
use Parth\LaravelRbacSuite\Rules\ConfiguredPasswordRule;
use Parth\LaravelRbacSuite\Tests\TestCase;

class PasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PasswordRule::ensureDefaultRules();
    }

    public function test_password_complexity_rules_enforced(): void
    {
        // Enable rules: Min Length 8, Uppercase, Lowercase, Number, Special Char
        PasswordRule::where('RuleCode', PasswordRule::RULE_MIN_LENGTH)->update(['IsEnabled' => true, 'RuleValue' => 8]);
        PasswordRule::where('RuleCode', PasswordRule::RULE_UPPERCASE)->update(['IsEnabled' => true]);
        PasswordRule::where('RuleCode', PasswordRule::RULE_LOWERCASE)->update(['IsEnabled' => true]);
        PasswordRule::where('RuleCode', PasswordRule::RULE_NUMBER)->update(['IsEnabled' => true]);
        PasswordRule::where('RuleCode', PasswordRule::RULE_SPECIAL_CHAR)->update(['IsEnabled' => true]);
        PasswordRule::clearCache();

        $rule = new ConfiguredPasswordRule;

        // Valid password
        $v1 = Validator::make(['password' => 'Secure@123'], ['password' => [$rule]]);
        $this->assertTrue($v1->passes());

        // Missing uppercase
        $v2 = Validator::make(['password' => 'secure@123'], ['password' => [$rule]]);
        $this->assertTrue($v2->fails());

        // Missing number
        $v3 = Validator::make(['password' => 'Secure@abc'], ['password' => [$rule]]);
        $this->assertTrue($v3->fails());

        // Missing special char
        $v4 = Validator::make(['password' => 'Secure1234'], ['password' => [$rule]]);
        $this->assertTrue($v4->fails());

        // Too short (< 8 chars)
        $v5 = Validator::make(['password' => 'Sec@1'], ['password' => [$rule]]);
        $this->assertTrue($v5->fails());
    }

    public function test_password_reset_flow(): void
    {
        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $user = $userModel::create([
            'name' => 'Reset User',
            'email' => 'reset.user@example.com',
            'password' => Hash::make('OldPassword@123'),
            'is_active' => '1',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'reset.user@example.com',
            'password' => 'NewSecret@456',
            'password_confirmation' => 'NewSecret@456',
        ]);

        $response->assertRedirect();
        $user->refresh();

        $this->assertTrue(Hash::check('NewSecret@456', $user->password));
    }
}
