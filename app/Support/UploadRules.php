<?php

namespace App\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * What an upload is allowed to be.
 *
 * Everything uploaded here is stored under storage/app/public, which is
 * symlinked into the web root as /uploads, and store() keeps the file's own
 * extension. So a file the server will execute is a file the internet can
 * run: that is how a web shell came to sit at /uploads/cacheohhhpk.php.
 *
 * The holes it came through were 'nullable|file', which accepts a .php as
 * readily as a .png, and several upload points that carried no rules at all,
 * including the one every student can reach by handing in written work.
 *
 * Every upload in the application is checked against this class. The image
 * rule is not only an extension check: Laravel's `image` reads the file and
 * fails anything that is not really an image, so a script renamed .png does
 * not get through either.
 */
class UploadRules
{
    public const IMAGE_EXTENSIONS = 'png,jpg,jpeg,webp,gif';

    public const IMAGE_MAX_KB = 8192;

    public const AUDIO_EXTENSIONS = 'mp3,wav,ogg,oga,m4a,mp4,webm,aac';

    public const AUDIO_MAX_KB = 51200;

    /**
     * A field that is always a file, or absent.
     *
     * @return list<string>
     */
    public static function image(): array
    {
        return ['nullable', 'image', 'mimes:'.self::IMAGE_EXTENSIONS, 'max:'.self::IMAGE_MAX_KB];
    }

    /**
     * A field that carries either text or a file, so the rules can only apply
     * to the file. A reading passage is text while a listening passage is an
     * audio file, and a match option is a word or a picture; both arrive
     * under the same key.
     *
     * @return list<string|Closure>
     */
    public static function imageIfUploaded(): array
    {
        return ['nullable', self::onlyWhenUploaded(self::image())];
    }

    /**
     * @return list<string|Closure>
     */
    public static function audioIfUploaded(): array
    {
        return ['nullable', self::onlyWhenUploaded([
            'file', 'mimes:'.self::AUDIO_EXTENSIONS, 'max:'.self::AUDIO_MAX_KB,
        ])];
    }

    /**
     * Apply rules to a value only when it is an upload, and let anything else
     * past untouched for the other rules on the field to judge.
     *
     * @param  list<string>  $rules
     */
    private static function onlyWhenUploaded(array $rules): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($rules): void {
            if (! $value instanceof UploadedFile) {
                return;
            }

            $check = Validator::make(['file' => $value], ['file' => $rules]);

            if ($check->fails()) {
                $fail($check->errors()->first('file'));
            }
        };
    }
}
