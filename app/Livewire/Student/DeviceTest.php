<?php

namespace App\Livewire\Student;

use Livewire\Component;
use App\Models\DeviceTest as DeviceTestModel;

class DeviceTest extends Component
{
    /**
     * Static array of fake questions — one per answer type.
     * Each entry mimics the $question object used by the real answer_type partials.
     */
    public array $fakeQuestions = [];

    public function mount()
    {
        $this->fakeQuestions = [
            [
                'id' => 'test_mcq',
                'type' => 'MCQs',
                'question' => 'Which animal says "Meow"? / ما الحيوان الذي يقول "مياو"؟',
                'image' => null,
                'options' => [
                    'a' => 'Cat / قطة',
                    'b' => 'Dog / كلب',
                    'c' => 'Bird / طائر',
                    'd' => 'Fish / سمكة',
                ],
            ],
            [
                'id' => 'test_tf',
                'type' => 'true-false',
                'question' => 'The sun rises in the morning. / تشرق الشمس في الصباح.',
                'image' => null,
                'options' => [
                    'true' => 'True / صحيح',
                    'false' => 'False / خطأ',
                ],
            ],
            [
                'id' => 'test_blanks',
                'type' => 'blanks',
                'question' => 'Complete the sentence: I drink ___ every day. / أكمل الجملة: أنا أشرب ___ كل يوم.',
                'image' => null,
                'options' => [],
            ],
            [
                'id' => 'test_typing',
                'type' => 'typing',
                'question' => 'Write 2-3 lines about your school. / اكتب سطرين أو ثلاثة عن مدرستك.',
                'image' => null,
                'options' => [],
            ],
            [
                'id' => 'test_rearrange',
                'type' => 'rearrange',
                'question' => 'I/أنا,go/أذهب,to/إلى,school/المدرسة,every/كل,day/يوم',
                'image' => null,
                'options' => [],
            ],
            [
                'id' => 'test_match',
                'type' => 'match',
                'question' => 'Match the animal with its sound. / صِل الحيوان بالصوت المناسب.',
                'image' => null,
                'isImgs' => false,
                'options' => [
                    'choice' => [
                        'Cat / قطة',
                        'Dog / كلب',
                    ],
                    'answer' => [
                        'Meow / مياو',
                        'Bark / نباح',
                    ],
                ],
            ],
            [
                'id' => 'test_match_images',
                'type' => 'match',
                'question' => 'Match the numbers with the correct arabic. / طابق الأرقام مع اللغة العربية الصحيحة ',
                'image' => null,
                'isImgs' => true,
                'options' => [
                    'choice' => [
                        'one.png',
                        'two.jfif',
                        'three.jfif',
                        'four.jfif',
                        'five.jfif'
                    ],
                    'answer' => [
                        'wahed.jfif',
                        'ethnan.jfif',
                        'thalatha.png',
                        'arba.png',
                        'khamsa.png'
                    ],
                ],
            ],
            [
                'id' => 'test_speaking',
                'type' => 'speaking-answer',
                'question' => 'Introduce yourself in English or Arabic. / عرّف بنفسك باللغة الإنجليزية أو العربية.',
                'image' => null,
                'options' => [],
            ],
            [
                'id' => 'test_writing',
                'type' => 'writing-answer',
                'question' => 'Write your favorite subject and why you like it. / اكتب مادتك المفضلة ولماذا تحبها.',
                'image' => null,
                'options' => [],
            ],
        ];
    }
    public function render()
    {
        // Convert each fake question array to an object so partials can use ->property syntax
        $questions = collect($this->fakeQuestions)->map(fn($q) => (object) $q);

        return view('livewire.student.device-test', [
            'questions' => $questions,
        ])->layout('layouts.app')->layoutData([
            'title' => 'Device Test',
        ]);
    }
}
