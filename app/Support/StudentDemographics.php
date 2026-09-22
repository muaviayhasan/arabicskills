<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Gender, SEN, G&T and Citizen: one place that decides how they are read from
 * a spreadsheet or form and how they are shown back.
 *
 * Every value has three states. null means "not recorded", which is kept
 * distinct from an explicit No so that students who were never filled in are
 * not reported as No.
 */
class StudentDemographics
{
    public const GENDERS = [
        'boy' => 'Boy',
        'girl' => 'Girl',
    ];

    /** Yes/No columns, keyed by database column => display label. */
    public const FLAGS = [
        'sen' => 'SEN',
        'gifted_talented' => 'G&T',
        'citizen' => 'Citizen',
    ];

    private const GENDER_ALIASES = [
        'boy' => 'boy', 'b' => 'boy', 'male' => 'boy', 'm' => 'boy',
        'girl' => 'girl', 'g' => 'girl', 'female' => 'girl', 'f' => 'girl',
    ];

    // "-" is how the client's own sheets write No.
    private const TRUE_VALUES = ['yes', 'y', 'true', '1'];

    private const FALSE_VALUES = ['no', 'n', 'false', '0', '-'];

    /**
     * Blank returns null (leave unchanged).
     *
     * @throws InvalidArgumentException for anything unrecognised
     */
    public static function parseGender(mixed $value): ?string
    {
        $key = self::normalise($value);

        if ($key === '') {
            return null;
        }

        if (! array_key_exists($key, self::GENDER_ALIASES)) {
            throw new InvalidArgumentException("Gender '{$value}' is not recognised. Use Boy or Girl.");
        }

        return self::GENDER_ALIASES[$key];
    }

    /**
     * Blank returns null (leave unchanged). "-" is an explicit No.
     *
     * @throws InvalidArgumentException for anything unrecognised
     */
    public static function parseFlag(mixed $value, string $label): ?bool
    {
        $key = self::normalise($value);

        if ($key === '') {
            return null;
        }

        if (in_array($key, self::TRUE_VALUES, true)) {
            return true;
        }

        if (in_array($key, self::FALSE_VALUES, true)) {
            return false;
        }

        throw new InvalidArgumentException("{$label} '{$value}' is not recognised. Use Yes, No or -.");
    }

    public static function genderLabel(?string $gender): string
    {
        return self::GENDERS[$gender] ?? '';
    }

    /** Matches the client's sheets: Yes, "-" for No, blank when not recorded. */
    public static function flagLabel(?bool $flag): string
    {
        return match ($flag) {
            true => 'Yes',
            false => '-',
            null => '',
        };
    }

    /**
     * Compact line for list views: gender plus any flag that is Yes, e.g.
     * "Boy · SEN · G&T". Empty when nothing is recorded, so students who have
     * not been filled in yet look the same as before.
     */
    public static function summary(object $student): string
    {
        $parts = array_filter([self::genderLabel($student->gender)]);

        foreach (self::FLAGS as $column => $label) {
            if ($student->{$column} === true) {
                $parts[] = $label;
            }
        }

        return implode(' · ', $parts);
    }

    /** A form <select> holds strings; map the stored value onto its option. */
    public static function toFormValue(?bool $flag): string
    {
        return match ($flag) {
            true => '1',
            false => '0',
            null => '',
        };
    }

    /** The reverse of toFormValue(). An unselected option becomes null. */
    public static function fromFormValue(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (bool) (int) $value;
    }

    /**
     * Copy of the form inputs with the four fields ready to save: selects hold
     * "" / "1" / "0", the database wants null / true / false.
     *
     * Returns a copy rather than editing the component's inputs, so a failed
     * save leaves the form's selects showing what the admin picked.
     */
    public static function normaliseFormInputs(array $inputs): array
    {
        $inputs['gender'] = ($inputs['gender'] ?? '') === '' ? null : $inputs['gender'];

        foreach (array_keys(self::FLAGS) as $column) {
            $inputs[$column] = self::fromFormValue($inputs[$column] ?? null);
        }

        return $inputs;
    }

    /** Validation rules shared by the Add and Edit student forms. */
    public static function formRules(): array
    {
        $rules = ['inputs.gender' => 'nullable|in:'.implode(',', array_keys(self::GENDERS))];

        foreach (array_keys(self::FLAGS) as $column) {
            $rules["inputs.{$column}"] = 'nullable|in:0,1';
        }

        return $rules;
    }

    private static function normalise(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }
}
