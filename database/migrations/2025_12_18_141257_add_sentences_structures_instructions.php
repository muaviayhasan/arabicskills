<?php

use App\Models\Instruction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Instruction::insert([
            'page' => 'sentences_structures_exam_instructions',
            'instructions' => '<h2 class="mb-4 fw-bold">Instructions</h2>
                <ol class="ps-3">
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                    <li><p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Non, illum!</p></li>
                </ol>'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Instruction::where("page", "sentences_structures_exam_instructions")->first()->delete();
    }
};
