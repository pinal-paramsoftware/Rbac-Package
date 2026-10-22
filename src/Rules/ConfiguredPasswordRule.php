<?php

namespace Parth\LaravelRbacSuite\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Parth\LaravelRbacSuite\Models\PasswordRule;

class ConfiguredPasswordRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param  iterable|null  $rules  Optional rules collection/array for manual overrides or testing
     */
    public function __construct(protected ?iterable $rules = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $activeRules = $this->rules ?? PasswordRule::getActiveRules();

        // 1. Minimum Length (enforce at least 8 characters when enabled, fallback to baseline 6)
        if (isset($activeRules[PasswordRule::RULE_MIN_LENGTH])) {
            $ruleObj = $activeRules[PasswordRule::RULE_MIN_LENGTH];
            $minVal = is_array($ruleObj) ? ($ruleObj['RuleValue'] ?? 8) : ($ruleObj->RuleValue ?? 8);
            $min = max((int) ($minVal ?: 8), 8);
            if (mb_strlen($value) < $min) {
                $fail("The :attribute must be at least {$min} characters.");
            }
        } else {
            $baselineMin = 6;
            if (mb_strlen($value) < $baselineMin) {
                $fail("The :attribute must be at least {$baselineMin} characters.");
            }
        }

        // 2. At least 1 uppercase letter (A-Z)
        if (isset($activeRules[PasswordRule::RULE_UPPERCASE])) {
            if (! preg_match('/[A-Z]/', $value)) {
                $fail('The :attribute must contain at least one uppercase letter.');
            }
        }

        // 3. At least 1 lowercase letter (a-z)
        if (isset($activeRules[PasswordRule::RULE_LOWERCASE])) {
            if (! preg_match('/[a-z]/', $value)) {
                $fail('The :attribute must contain at least one lowercase letter.');
            }
        }

        // 4. At least 1 number (0-9)
        if (isset($activeRules[PasswordRule::RULE_NUMBER])) {
            if (! preg_match('/[0-9]/', $value)) {
                $fail('The :attribute must contain at least one number.');
            }
        }

        // 5. At least 1 special character (non-alphanumeric character)
        if (isset($activeRules[PasswordRule::RULE_SPECIAL_CHAR])) {
            if (! preg_match('/[^A-Za-z0-9]/', $value)) {
                $fail('The :attribute must contain at least one special character.');
            }
        }
    }
}
