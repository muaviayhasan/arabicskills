<?php

use App\Http\Controllers\Controller;
use App\Livewire\Student\Instructions;
use App\Livewire\Student\Exams\SkillExams;
use App\Livewire\Student\StudentDashboard;
use App\Livewire\Student\Auth\StudentLogin;
use App\Livewire\Student\Exams\ReadingExam;
use App\Livewire\Student\Exams\WritingExam;
use App\Http\Controllers\TakeExamController;
use App\Livewire\Student\Exams\SpeakingExam;
use App\Livewire\Student\Exams\ListeningExam;
use App\Livewire\Student\Exams\ExamInstructions;
use App\Livewire\Student\Exams\SentencesStructuresExam;
use App\Http\Controllers\StudentQrLoginController;

Route::middleware('guest:web')->get('/login', StudentLogin::class)->name('login');

Route::middleware(['signed'])->get('/qr-login/{student}', StudentQrLoginController::class)->name('student.qr-login');


Route::middleware(['auth:web'])->prefix('/student')->group(function () {

    // Dashboard Route
    Route::get('/dashboard', StudentDashboard::class)->name('student.dashboard');

    // Device Test
    Route::get('/device-test', \App\Livewire\Student\DeviceTest::class)->name('student.device-test');
    Route::get('/device-test/result/{id}', \App\Livewire\Student\DeviceTestResult::class)->name('student.device-test.result');

    Route::get('/instructions', Instructions::class)->name('student.instructions');

    Route::get('/logout', [Controller::class, 'logout'])->name('student.logout');

    Route::get('/exams', SkillExams::class)->name('student.exams');

    Route::get('/exams/instruction/{type}', ExamInstructions::class)->name('student.exams-instructions');

    Route::get('/exams/reading-exam', ReadingExam::class)->name('student.reading-exam');
    Route::get('/exams/listening-exam', ListeningExam::class)->name('student.listening-exam');
    Route::get('/exams/writing-exam', WritingExam::class)->name('student.writing-exam');
    Route::get('/exams/speaking-exam', SpeakingExam::class)->name('student.speaking-exam');
    Route::get('/exams/sentences-structures-exam', SentencesStructuresExam::class)->name('student.sentences-structures-exam');


    // take exam routes

    Route::controller(TakeExamController::class)->group(function () {
        Route::post('/exams/reading-exam', 'saveReadExam');
        Route::post('/exams/listening-exam', 'saveListenExam');
        Route::post('/exams/writing-exam', 'saveWritingExam');
        Route::post('/exams/speaking-exam', 'saveSpeakingExam');
        Route::post('/exams/sentences-structures-exam', 'saveSentencesStructuresExam');
    });
    
    Route::post('/device-test/submit', [\App\Http\Controllers\DeviceTestController::class, 'submitTest'])->name('student.device-test.submit');
});
