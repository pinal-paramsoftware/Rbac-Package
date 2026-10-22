<?php

namespace Parth\LaravelRbacSuite\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Parth\LaravelRbacSuite\Models\PasswordRule;

class DefaultPasswordRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $rules = [
            [
                'RuleCode' => PasswordRule::RULE_MIN_LENGTH,
                'RuleName' => 'Minimum Length',
                'RuleValue' => 8,
                'IsEnabled' => 0,
                'IsActive' => 1,
            ],
            [
                'RuleCode' => PasswordRule::RULE_UPPERCASE,
                'RuleName' => 'Uppercase Letters',
                'RuleValue' => 1,
                'IsEnabled' => 0,
                'IsActive' => 1,
            ],
            [
                'RuleCode' => PasswordRule::RULE_LOWERCASE,
                'RuleName' => 'Lowercase Letters',
                'RuleValue' => 1,
                'IsEnabled' => 0,
                'IsActive' => 1,
            ],
            [
                'RuleCode' => PasswordRule::RULE_NUMBER,
                'RuleName' => 'Numbers',
                'RuleValue' => 1,
                'IsEnabled' => 0,
                'IsActive' => 1,
            ],
            [
                'RuleCode' => PasswordRule::RULE_SPECIAL_CHAR,
                'RuleName' => 'Special Characters',
                'RuleValue' => 1,
                'IsEnabled' => 0,
                'IsActive' => 1,
            ],
        ];

        foreach ($rules as $rule) {
            PasswordRule::firstOrCreate(
                ['RuleCode' => $rule['RuleCode']],
                array_merge($rule, [
                    'CreatedOn' => $now,
                    'UpdatedOn' => $now,
                ])
            );
        }

        PasswordRule::clearCache();
    }
}
