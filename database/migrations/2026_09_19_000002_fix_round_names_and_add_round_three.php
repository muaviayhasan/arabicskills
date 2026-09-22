<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Correct two misspelled round names and add Round Three for the current year.
 *
 * Round names are not just labels: exams.term and activities.term store the
 * exact text, and the question bank, exams and the marks import all match on
 * it. Renaming only the setting would disconnect every exam and activity
 * from its round, so all three places are updated together.
 */
return new class extends Migration
{
    private const RENAMES = [
        'Roud Two 2026 - 2027' => 'Round Two 2026 - 2027',
        'Round One 2026- 2027' => 'Round One 2026 - 2027',
    ];

    private const ROUND_THREE = 'Round Three 2026 - 2027';

    public function up(): void
    {
        DB::transaction(function () {
            $this->renameEverywhere(self::RENAMES);

            $this->updateTermsSetting(function (array $terms) {
                if (! in_array(self::ROUND_THREE, $terms, true)) {
                    $terms[] = self::ROUND_THREE;
                }

                return $terms;
            });
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $this->renameEverywhere(array_flip(self::RENAMES));

            // Only take Round Three away if nothing has been filed under it yet.
            $inUse = DB::table('exams')->where('term', self::ROUND_THREE)->exists()
                || DB::table('activities')->where('term', self::ROUND_THREE)->exists();

            if (! $inUse) {
                $this->updateTermsSetting(fn (array $terms) => array_values(
                    array_filter($terms, fn ($term) => $term !== self::ROUND_THREE)
                ));
            }
        });
    }

    /**
     * @param  array<string, string>  $renames  old name => new name
     */
    private function renameEverywhere(array $renames): void
    {
        foreach ($renames as $from => $to) {
            // Query builder, not Eloquent: archived (soft-deleted) exams are renamed too.
            DB::table('exams')->where('term', $from)->update(['term' => $to]);
            DB::table('activities')->where('term', $from)->update(['term' => $to]);
        }

        $this->updateTermsSetting(fn (array $terms) => array_map(
            fn ($term) => $renames[$term] ?? $term,
            $terms
        ));
    }

    /**
     * The terms setting is a PHP-serialized array of round names.
     *
     * @param  callable(array<int, string>): array<int, string>  $change
     */
    private function updateTermsSetting(callable $change): void
    {
        $option = DB::table('options')->where('key', 'terms')->first();

        if (! $option) {
            return;
        }

        $terms = @unserialize((string) $option->value);

        if (! is_array($terms)) {
            return;
        }

        DB::table('options')
            ->where('id', $option->id)
            ->update(['value' => serialize(array_values($change($terms)))]);
    }
};
