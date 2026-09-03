<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Auth\Adminino;
use App\Livewire\Admin\Exams\AddExam;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Exams\AllExams;
use App\Livewire\Admin\Exams\EditExam;
use App\Livewire\Admin\Admins\AddAdmin;
use Illuminate\Support\Facades\Artisan;
use App\Livewire\Admin\Admins\AllAdmins;
use App\Livewire\Admin\Admins\EditAdmin;
use App\Livewire\Admin\Options\Settings;
use App\Http\Controllers\AdminController;
use App\Livewire\Admin\Schools\AddSchool;
use App\Livewire\Admin\Auth\ResetPassword;
use App\Livewire\Admin\Schools\AllSchools;
use App\Livewire\Admin\Schools\EditSchool;
use App\Livewire\Admin\Students\AddStudent;
use App\Livewire\Admin\Auth\RequestPassword;
use App\Livewire\Admin\Profile\AdminProfile;
use App\Livewire\Admin\Students\AllStudents;
use App\Livewire\Admin\Students\EditStudent;
use App\Http\Controllers\QuestionBankController;
use App\Livewire\Admin\Schools\GradesAndSections;
use App\Livewire\Admin\QuestionBanks\AddQuestionBank;
use App\Livewire\Admin\QuestionBanks\AllQuestionBanks;
use App\Livewire\Admin\QuestionBanks\EditQuestionBank;
use App\Livewire\Admin\RolesAndPermission\RolePermission;
use App\Livewire\Admin\RolesAndPermission\EditRolePermission;
use App\Livewire\Admin\Students\ImportStudent;

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

Route::get('/install', function () {
    // if is not production
    if (env('APP_ENV') !== 'production') {
        Artisan::call('migrate:fresh --seed');
        Artisan::call('storage:link');
    }
});

Route::get('/migrate', function () {
    Artisan::call('migrate');
});

Route::get('/', function () {
    // auth()->logout();
    return redirect()->route('login');
});

Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    return redirect()->route('login');
});