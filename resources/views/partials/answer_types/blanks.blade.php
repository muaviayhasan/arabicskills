 <div wire:ignore class="quiz-box py-4">
     <div class="row g-2">
         <h4 class="m-0 fs-5 d-flex">

             <span class="text-danger fs-5 me-3 fw-bold"><i class="fa-solid fa-arrow-right"></i></span>
             <div>
                 {!! $question->question !!}
             </div>
         </h4>

         @if ($question->image)
             <div class="col-12 mb-2 text-center">
                 <img src="{{ read_image($question->image) }}" class="quiz-img" style="max-width: 100%;" height="300px">
             </div>
         @endif
         <input type="text" name="answer[{{ $question->id }}][type]" id="" value="blanks" hidden>
         <div class="col-12 mt-3">

             <div class="form-area">
                 <textarea name="answer[{{ $question->id }}][answer]" class="form-control anstextarea no-copy-paste"
                     placeholder="Write your answer" style="height: 50px" id="textInput_{{ $question->id }}"></textarea>

                 <button type="button" class="d-none d-lg-block showKeyboardBtn"></button>
             </div>
             <span class="text-danger text-error" id="answer.{{ $question->id }}.answer"></span>

         </div>
     </div>
 </div>
