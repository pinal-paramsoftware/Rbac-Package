<?php

namespace Parth\LaravelRbacSuite\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Parth\LaravelRbacSuite\Rules\ConfiguredPasswordRule;

class PasswordRule extends Model
{
    public const RULE_MIN_LENGTH = 'MIN_LENGTH';

    public const RULE_UPPERCASE = 'UPPERCASE';

    public const RULE_LOWERCASE = 'LOWERCASE';

    public const RULE_NUMBER = 'NUMBER';

    public const RULE_SPECIAL_CHAR = 'SPECIAL_CHAR';

    protected $primaryKey = 'RuleId';

    public $timestamps = false;

    protected $fillable = [
        'RuleCode',
        'RuleName',
        'RuleValue',
        'IsEnabled',
        'CreatedOn',
        'UpdatedOn',
        'IsActive',
    ];

    protected $casts = [
        'IsEnabled' => 'boolean',
        'IsActive' => 'boolean',
        'RuleValue' => 'integer',
    ];

    public function getTable(): string
    {
        return config('rbac-suite.tables.password_rules', 'password_rules');
    }

    protected static function booted(): void
    {
        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    /**
     * Clear cached active password rules.
     */
    public static function clearCache(): void
    {
        Cache::forget('active_password_rules');
    }

    /**
     * Default password rules that must exist in the table.
     *
     * @return array<int, array<string, int|string|bool>>
     */
    public static function defaultRules(): array
    {
        return [
            [
                'RuleCode' => self::RULE_MIN_LENGTH,
                'RuleName' => 'Minimum Length',
                'RuleValue' => 8,
                'IsEnabled' => false,
                'IsActive' => true,
            ],
            [
                'RuleCode' => self::RULE_UPPERCASE,
                'RuleName' => 'Uppercase Letters',
                'RuleValue' => 1,
                'IsEnabled' => false,
                'IsActive' => true,
            ],
            [
                'RuleCode' => self::RULE_LOWERCASE,
                'RuleName' => 'Lowercase Letters',
                'RuleValue' => 1,
                'IsEnabled' => false,
                'IsActive' => true,
            ],
            [
                'RuleCode' => self::RULE_NUMBER,
                'RuleName' => 'Numbers',
                'RuleValue' => 1,
                'IsEnabled' => false,
                'IsActive' => true,
            ],
            [
                'RuleCode' => self::RULE_SPECIAL_CHAR,
                'RuleName' => 'Special Characters',
                'RuleValue' => 1,
                'IsEnabled' => false,
                'IsActive' => true,
            ],
        ];
    }

    /**
     * Ensure all default rules exist in the table.
     */
    public static function ensureDefaultRules(): void
    {
        $now = Carbon::now();

        foreach (self::defaultRules() as $rule) {
            self::firstOrCreate(
                ['RuleCode' => $rule['RuleCode']],
                array_merge($rule, [
                    'CreatedOn' => $now,
                    'UpdatedOn' => $now,
                ])
            );
        }
    }

    /**
     * Get all active and enabled password rules keyed by RuleCode.
     *
     * @return Collection<string, self>
     */
    public static function getActiveRules()
    {
        return Cache::rememberForever('active_password_rules', function () {
            self::ensureDefaultRules();

            return self::where('IsActive', 1)
                ->where('IsEnabled', 1)
                ->get()
                ->keyBy('RuleCode');
        });
    }

    /**
     * Centralized validation rules for passwords across the application.
     *
     * @param  bool  $confirmed  Whether to require password_confirmation field
     * @return array<int, string|ValidationRule>
     */
    public static function getValidationRules(bool $confirmed = true): array
    {
        $rules = [
            'required',
            'string',
            new ConfiguredPasswordRule,
        ];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }
}
