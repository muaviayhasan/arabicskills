<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Admin;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\AdminRolePermission;
use App\Models\Grade;
use App\Models\Instruction;
use App\Models\Option;
use App\Models\School;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $ar = ['A', 'A+', 'B', 'B+', 'C', 'C+'];

        Option::insert([
            ['key' => 'web_name', 'value' => ''],
            ['key' => 'web_email', 'value' => ''],
            ['key' => 'logo', 'value' => 'logo'],
            ['key' => 'terms', 'value' => serialize(['midterm', 'summer', 'sessional'])],
            ['key' => 'current_academic_year', 'value' => '2026'],
        ]);
        AdminRole::insert([
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
            ],
        ]);

        Admin::insert([
            [
                'role_id' => 1,
                'first_name' => 'Murad',
                'last_name' => 'Ali',
                'email' => 'admin@softwareflare.com',
                'password' => Hash::make('123456'),
                'image' => 'no-image.png',
            ],
        ]);

        AdminPermission::insert([

            [
                'key' => 'admin_roles',
                'name' => 'Admin Roles',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'key' => 'schools',
                'name' => 'Schools',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],

            [
                'key' => 'classes',
                'name' => 'Classes',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],

            [
                'key' => 'students',
                'name' => 'Students',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],

            [
                'key' => 'question_banks',
                'name' => 'Question Banks',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],

            [
                'key' => 'exams',
                'name' => 'Exams',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],

            [
                'key' => 'results',
                'name' => 'Results',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],

            [
                'key' => 'profile',
                'name' => 'Profile',
                'view' => 1,
                'add' => 0,
                'edit' => 1,
                'delete' => 0,
            ],
            [
                'key' => 'admins',
                'name' => 'Admins',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'key' => 'options',
                'name' => 'Options',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
        ]);

        foreach (AdminPermission::all() as $permission) {
            AdminRolePermission::insert(
                [
                    'role_id' => 1,
                    'permission_id' => $permission->id,
                    'view' => $permission->view,
                    'add' => $permission->add,
                    'edit' => $permission->edit,
                    'delete' => $permission->delete,
                ]
            );
        }

        School::create([
            'name' => 'roots school system',
        ]);

        Grade::insert([[
            'name' => '1',
            'admin_id' => 1,
            'level' => 'A',
        ], [
            'name' => '2',
            'admin_id' => 1,
            'level' => 'A',
        ], [
            'name' => '3',
            'admin_id' => 1,
            'level' => 'A+',
        ], [
            'name' => '4',
            'admin_id' => 1,
            'level' => 'A+',
        ], [
            'name' => '5',
            'admin_id' => 1,
            'level' => 'B',
        ], [
            'name' => '6',
            'admin_id' => 1,
            'level' => 'B',
        ], [
            'name' => '7',
            'admin_id' => 1,
            'level' => 'B+',
        ], [
            'name' => '8',
            'admin_id' => 1,
            'level' => 'B+',
        ], [
            'name' => '9',
            'admin_id' => 1,
            'level' => 'C',
        ], [
            'name' => '10',
            'admin_id' => 1,
            'level' => 'C',
        ], [
            'name' => '11',
            'admin_id' => 1,
            'level' => 'C+',
        ], [
            'name' => '12',
            'admin_id' => 1,
            'level' => 'C+',
        ]]);

        Section::insert([
            [
                'name' => 'A',
                'grade_id' => 1,
                'school_id' => 1,
            ],
            [
                'name' => 'B',
                'grade_id' => 1,
                'school_id' => 1,
            ],
            [
                'name' => 'C',
                'grade_id' => 1,
                'school_id' => 1,
            ],
        ]);

        Instruction::upsert(
            [
                [
                    'page' => 'dashboard_instructions',
                    'instructions' => '<h2 class="mb-4 fw-bold">Instructions</h2>
                <ol class="ps-3">
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                </ol>',
                ],
                [
                    'page' => 'reading_exam_instructions',
                    'instructions' => '<h2 class="mb-4 fw-bold">Instructions</h2>
                <ol class="ps-3">
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                </ol>',
                ],
                [
                    'page' => 'listening_exam_instructions',
                    'instructions' => '<h2 class="mb-4 fw-bold">Instructions</h2>
                <ol class="ps-3">
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                </ol>',
                ],
                [
                    'page' => 'writing_exam_instructions',
                    'instructions' => '<h2 class="mb-4 fw-bold">Instructions</h2>
                <ol class="ps-3">
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                </ol>',
                ],
                [
                    'page' => 'speaking_exam_instructions',
                    'instructions' => '<h2 class="mb-4 fw-bold">Instructions</h2>
                <ol class="ps-3">
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                </ol>',
                ],

                [
                    'page' => 'sentences_structures_exam_instructions',
                    'instructions' => '<h2 class="mb-4 fw-bold">Instructions</h2>
                <ol class="ps-3">
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                </ol>',
                ],
            ],
            ['page'],
            ['instructions']
        );
    }
}
