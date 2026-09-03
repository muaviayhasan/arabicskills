<?php

namespace App\Support;

class QuestionTypeDefaults
{
    public static function question(string $type, string $lang = 'english'): string
    {
        $lang = $lang === 'arabic' ? 'arabic' : 'english';

        $defaults = [
            'MCQs' => [
                'english' => '<p>Choose the correct answer</p>',
                'arabic' => '<p>اِّخْ تَرْ اإلِّجَابَة َ الصَّحِّ يحَة َ</p>',
            ],
            'true-false' => [
                'english' => '<p>Mark the correct statements with (True) and the incorrect ones with (False).</p>',
                'arabic' => '<p>حَدّ ِّد ال ْعِّبَارَ اتِّ الصَّحِّ يحَة َ ب ِّعََل َ مَةِّ (صَحّ)، وَ ال ْعِّبَارَ اتِّ ال ْخَ اطِّئَة َ ب ِّعََل َ مَةِّ  . (خَطَ أ)</p>',
            ],
            'blanks' => [
                'english' => '<b>Fill in the blanks </b><br><br>',
                'arabic' => '<b>املأ الفراغات</b><br><br>',
            ],
            'typing' => [
                'english' => '<b>Answer the question</b><br><br>',
                'arabic' => '<b>أجب على السؤال</b><br><br>',
            ],
            'match' => [
                'english' => '<strong>Drag correct picture to correct option</strong><br><br>',
                'arabic' => '<strong>اسحب الصورة الصحيحة إلى الخيار الصحيح</strong><br><br>',
            ],
            'match_words' => [
                'english' => '<strong>Drag correct word to correct option</strong><br><br>',
                'arabic' => '<strong>اسحب الكلمة الصحيحة إلى الخيار الصحيح</strong><br><br>',
            ],
        ];

        return $defaults[$type][$lang] ?? '';
    }
}
