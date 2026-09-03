<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\QuestionBankController;
use App\Livewire\Admin\Admins\AddAdmin;
use App\Livewire\Admin\Admins\AllAdmins;
use App\Livewire\Admin\Admins\EditAdmin;
use App\Livewire\Admin\Auth\Adminino;
use App\Livewire\Admin\Auth\RequestPassword;
use App\Livewire\Admin\Auth\ResetPassword;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ExamCheck\AllResults;
use App\Livewire\Admin\ExamCheck\AttemptedExams;
use App\Livewire\Admin\ExamCheck\CheckExam;
use App\Livewire\Admin\ExamCheck\ShowPaper;
use App\Livewire\Admin\ExamCheck\StudentExamDetails;
use App\Livewire\Admin\Exams\Activities\ExamListeningActivities;
use App\Livewire\Admin\Exams\Activities\ExamReadingActivities;
use App\Livewire\Admin\Exams\Activities\ExamSentencesStructuresActivities;
use App\Livewire\Admin\Exams\Activities\ExamSpeakingActivities;
use App\Livewire\Admin\Exams\Activities\ExamWritingActivities;
use App\Livewire\Admin\Exams\AddExam;
use App\Livewire\Admin\Exams\AllExams;
use App\Livewire\Admin\Exams\EditExam;
use App\Livewire\Admin\Exams\Preview\PreviewTypeExam;
use App\Livewire\Admin\Exams\Preview\PreviewTypes;
use App\Livewire\Admin\Levels\LevelsCrud;
use App\Livewire\Admin\Logs\ShowLogs;
use App\Livewire\Admin\Options\InstructionPages;
use App\Livewire\Admin\Options\Settings;
use App\Livewire\Admin\Profile\AdminProfile;
use App\Livewire\Admin\QuestionBanks\AddQuestionBank;
use App\Livewire\Admin\QuestionBanks\AllQuestionBanks;
use App\Livewire\Admin\QuestionBanks\EditQuestionBank;
use App\Livewire\Admin\RolesAndPermission\EditRolePermission;
use App\Livewire\Admin\RolesAndPermission\RolePermission;
use App\Livewire\Admin\Schools\AddSchool;
use App\Livewire\Admin\Schools\AllSchools;
use App\Livewire\Admin\Schools\EditSchool;
use App\Livewire\Admin\Schools\GradesAndSections;
use App\Livewire\Admin\Schools\GradeYear;
use App\Livewire\Admin\Students\AddStudent;
use App\Livewire\Admin\Students\AllStudents;
use App\Livewire\Admin\Students\EditStudent;
use App\Livewire\Admin\Students\ImportStudent;
use App\Livewire\Admin\Students\StudentsWithoutExams;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('guest:admin')->get('/adminino', Adminino::class)->name('admin.login');

Route::view('/access-denied', 'accessDenied')->name('access-denied');
Route::get('/coming-soon', function () {
    return view('comming-soon');
})->name('coming-soon');
Route::get('/download-sample', [Controller::class, 'downloadSample'])->name('download-sample');
// admin routes

Route::middleware('guest:admin')->get('/adminino/reset-password-request', RequestPassword::class)->name('reset-password-request');

Route::middleware('guest:admin')->get('/adminino/reset-password/{token}', ResetPassword::class)->name('reset-password');

Route::middleware(['auth:admin', 'permissions'])->prefix('/adminino')->group(function () {

    // Dashboard Route
    Route::get('/dashboard', Dashboard::class)->name('admin.dashboard');

    // logout route
    Route::controller(AdminController::class)->group(function () {
        Route::post('/fetch-activities', 'fetchActivities')->name('admin.fetch-activities');
        Route::get('/logout', 'adminLogout')->name('admin.logout');
    });

    // roles and permission routes
    Route::get('/role-permission', RolePermission::class)->name('admin.role-permission')->middleware('permissions:admin_roles,view');
    Route::get('/role-permission/edit-role-permission/{role_id}', EditRolePermission::class)->name('admin.edit-role-permission')->middleware('permissions:admin_roles,edit');

    // profile routes
    Route::get('/profile', AdminProfile::class)->name('admin.profile')->middleware('permissions:profile,view');

    // all grades
    Route::get('/grades', GradeYear::class)->name('admin.grades')->middleware('permissions:classes,view');

    // schools routes

    Route::get('/schools', AllSchools::class)->name('admin.schools')->middleware('permissions:schools,view');
    Route::get('/schools/add-school', AddSchool::class)->name('admin.add-school')->middleware('permissions:schools,add');
    Route::get('/schools/edit-school/{school_id}', EditSchool::class)->name('admin.edit-school')->middleware('permissions:schools,edit');

    // school sections
    Route::get('/schools/grades-and-sections/{school_id}', GradesAndSections::class)->name('admin.grades-and-sections')->middleware('permissions:classes,edit');

    // admins routes
    Route::get('/admins', AllAdmins::class)->name('admin.admins')->middleware('permissions:admins,view');
    Route::get('/admins/add-admin', AddAdmin::class)->name('admin.add-admin')->middleware('permissions:admins,add');
    Route::get('/admins/edit-admin/{admin_id}', EditAdmin::class)->name('admin.edit-admin')->middleware('permissions:admins,edit');

    // students routes
    Route::get('/students/import/{school_id?}', ImportStudent::class)->name('admin.import-students')->middleware('permissions:students,view');
    Route::get('/students/without-exams', StudentsWithoutExams::class)->name('admin.students-without-exams')->middleware('permissions:students,view');
    Route::get('/students/{school_id?}', AllStudents::class)->name('admin.students')->middleware('permissions:students,view');
    Route::get('/students/student/add-student/{school_id?}', AddStudent::class)->name('admin.add-student')->middleware('permissions:students,add');
    Route::get('/students/student/edit-student/{student}', EditStudent::class)->name('admin.edit-student')->middleware('permissions:students,edit');

    // device tests routes
    Route::get('/device-tests', \App\Livewire\Admin\DeviceTests\AllDeviceTests::class)->name('admin.device-tests')->middleware('permissions:students,view');
    Route::get('/device-tests/{id}', \App\Livewire\Admin\DeviceTests\DeviceTestDetails::class)->name('admin.device-tests.details')->middleware('permissions:students,view');

    // question-banks routes
    Route::get('/question-banks/{grade_id?}', AllQuestionBanks::class)->name('admin.question-banks')->middleware('permissions:question_banks,view');
    Route::get('/question-banks/question/add/{type}', AddQuestionBank::class)->name('admin.add-question-bank')->middleware('permissions:question_banks,add');
    Route::get('/question-banks/question/edit/{activity_id}', EditQuestionBank::class)->name('admin.edit-question-bank')->middleware('permissions:question_banks,edit');

    Route::controller(QuestionBankController::class)->group(function () {

        Route::get('/set-activity-type-input', 'setActivityType');
        Route::get('/set-question-type-input', 'setQuestionType');
        Route::post('/question-banks/question/add', 'addQuestion');
        Route::post('/question-banks/question/edit', 'updateQuestion');
    });

    // exams routes
    Route::get('/exams/preview/{exam}/{type}', PreviewTypeExam::class)->name('admin.exam-preview-type')->middleware('permissions:exams,view');
    Route::get('/exams/preview/{exam}', PreviewTypes::class)->name('admin.exam-preview-types')->middleware('permissions:exams,view');
    Route::get('/exams/{school_id?}', AllExams::class)->name('admin.exams')->middleware('permissions:exams,view');
    Route::get('/exams/exam/add-exam/', AddExam::class)->name('admin.add-exam')->middleware('permissions:exams,add');
    Route::get('/exams/exam/edit-exam/{exam}', EditExam::class)->name('admin.edit-exam')->middleware('permissions:exams,edit');

    // exam activities
    Route::get('exams/activities/reading/{exam_id}', ExamReadingActivities::class)->name('admin.exam-reading-activities')->middleware('permissions:exams,edit');
    Route::get('exams/activities/listening/{exam_id}', ExamListeningActivities::class)->name('admin.exam-listening-activities')->middleware('permissions:exams,edit');
    Route::get('exams/activities/writing/{exam_id}', ExamWritingActivities::class)->name('admin.exam-writing-activities')->middleware('permissions:exams,edit');
    Route::get('exams/activities/speaking/{exam_id}', ExamSpeakingActivities::class)->name('admin.exam-speaking-activities')->middleware('permissions:exams,edit');

    Route::get('exams/activities/sentences_structures/{exam_id}', ExamSentencesStructuresActivities::class)->name('admin.exam-sentences-structures-activities')->middleware('permissions:exams,edit');

    // check exam routes
    Route::get('/check-exam', AttemptedExams::class)->name('admin.attempted-exams')->middleware('permissions:results,view');

    Route::get('/check-exam/{exam_id}/details', StudentExamDetails::class)->name('admin.student-exam.details')->middleware('permissions:results,edit');

    Route::get('/check-exam/{type}/{exam_id}', CheckExam::class)->name('admin.check-exam')->middleware('permissions:results,edit');
    Route::get('/results/{school?}', AllResults::class)->name('admin.results')->middleware('permissions:results,view');
    Route::get('/results/{type}/{exam_id}', ShowPaper::class)->name('admin.show-exam')->middleware('permissions:results,view');

    // setting routes
    Route::get('/settings', Settings::class)->name('admin.settings')->middleware('permissions:options,view');
    Route::get('/levels', LevelsCrud::class)->name('admin.levels')->middleware('permissions:levels,view');
    Route::get('/instruction-pages', InstructionPages::class)->name('admin.instruction-pages')->middleware('permissions:options,view');

    Route::get('/auth-logs', ShowLogs::class)->name('admin.auth-logs');
});
