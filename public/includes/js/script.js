
// ==========================clickable-match-options (NEW CLICK-BASED SYSTEM)
$(document).ready(function () {
  
  // Click handler for match options - SELECT OPTION FIRST
  $('.clickable-match').on('click', function (event) {
    event.preventDefault();

    const $matchOption = $(this);

    // Check if this option is already used
    if ($matchOption.hasClass('matched')) {
      return;
    }

    const $quizBox = $matchOption.closest('.quiz-box');

    // Remove selection only within the same question
    $quizBox.find('.clickable-match').removeClass('selected');
    
    // Select this option
    $matchOption.addClass('selected');
  });

  // Click handler for match word boxes - THEN CLICK TARGET TO ASSIGN
  $('.match-word-box').on('click', function (event) {
    event.preventDefault();
    
    const $matchBox = $(this);
    
    // Don't allow if already has a match
    if ($matchBox.children('.tooltip').length > 0) {
      return;
    }

    const $quizBox = $matchBox.closest('.quiz-box');

    // Find selected option within the same question only
    const $selectedOption = $quizBox.find('.clickable-match.selected');
    
    if ($selectedOption.length === 0) {
      // No option selected, show a temporary message
      $matchBox.addClass('pulse-warning');
      setTimeout(() => {
        $matchBox.removeClass('pulse-warning');
      }, 500);
      return;
    }

    handleClickMatch($matchBox, $selectedOption);
    
    // Remove selection from the option
    $selectedOption.removeClass('selected');
  });
});

function handleClickMatch($matchBox, $matchOption) {
  if ($matchBox.closest('.quiz-box')[0] !== $matchOption.closest('.quiz-box')[0]) {
    return;
  }

  const boxQuestionId = $matchBox.data('question-id');
  const optionQuestionId = $matchOption.data('question-id');
  if (boxQuestionId && optionQuestionId && String(boxQuestionId) !== String(optionQuestionId)) {
    return;
  }

  const activity = $matchBox.attr("data-choice") || $matchBox.attr("data-activity");
  const matchInput = $matchBox.attr("data-input");

  const $input = $("input[name='" + matchInput + "']");
  let val = JSON.parse($input.val());

  const tooltipText = $matchOption.html();
  const answer = $matchOption.attr("data-answer");

  val[activity] = answer;
  $input.val(JSON.stringify(val));

  const tooltip = $('<span class="tooltip text-center"><span class="close-btn"><i class="fa-solid fa-times"></i></span>' + tooltipText + '</span>');

  if (!$matchBox.children('.tooltip').length) {
    tooltip.find('.close-btn').on('click', function (e) {
      e.stopPropagation();
      $(this).parent('.tooltip').remove();
      $matchOption.removeClass('matched').show();

      delete val[activity];
      $input.val(JSON.stringify(val));
    });

    $matchBox.append(tooltip);
    $matchOption.addClass('matched');
  } else {
    $matchBox.addClass('not-allowed-drop');
    setTimeout(() => $matchBox.removeClass('not-allowed-drop'), 800);
  }
}

// OLD DRAG & DROP CODE (KEPT FOR BACKWARDS COMPATIBILITY - CAN BE REMOVED IF NOT NEEDED)
/*
$(document).ready(function () {
  var draggedItem = null;
  var longPressTimer;
  var isScrolling = false;

  $('.draggable').on('dragstart', function (event) {
    draggedItem = $(this);
    event.originalEvent.dataTransfer.setData('text/plain', 'dragged');
  });

  $('.match-word-box').on('dragover', function (event) {
    event.preventDefault();
  });

  $('.match-word-box').on('drop', function (event) {
    event.preventDefault();

    var activity = $(this).attr("data-choice");
    var daragsInput = $(this).attr("data-input");

    daragsInput = $("input[name='" + daragsInput + "']");

    let val = JSON.parse(daragsInput.val());


    // daragsInput.val(val);


    var data = event.originalEvent.dataTransfer.getData('text/plain');

    if (data === 'dragged' && draggedItem !== null) {
      var draggedElement = draggedItem;
      var tooltipText = draggedElement.html();

      var answer = draggedElement.attr("data-answer");
      val[`${activity}`] = answer;
      daragsInput.val(JSON.stringify(val));


      var tooltip = $('<span class="tooltip"><span class="close-btn">×</span>' + tooltipText + '</span>');

      if (!$(this).children('.tooltip').length) {
        tooltip.find('.close-btn').on('click', function () {
          $(this).parent('.tooltip').remove();
          draggedElement.show();

          delete val[`${activity}`];
          daragsInput.val(JSON.stringify(val));

        });

        $(this).append(tooltip);
        draggedElement.hide();
      } else {
        $(this).addClass('not-allowed-drop');
        setTimeout(function () {
          $(this).removeClass('not-allowed-drop');
        }.bind(this), 1000);
      }
    }
  });

  let touchDraggedItem = null;
  let touchClone = null;
  let droppedSuccessfully = false;

  $('.draggable').on('touchstart', function (e) {
    e.preventDefault();

    touchDraggedItem = $(this);
    droppedSuccessfully = false;

    const touch = e.originalEvent.touches[0];

    // 👇 Create SMALL fixed-size clone
    touchClone = touchDraggedItem.clone()
      .removeAttr('style')
      .css({
        position: 'fixed',
        width: '100px',
        maxWidth: '100px',
        maxHeight: '40px',        // ✅ HARD HEIGHT LIMIT
        padding: '4px 6px',       // ✅ RESET PADDING
        overflow: 'hidden',       // ✅ PREVENT GROW
        fontSize: '12px',         // ✅ FIX FONT SIZE
        lineHeight: '1.2',        // ✅ FIX LINE HEIGHT
        left: touch.clientX - 50,
        top: touch.clientY - 20,
        opacity: 0.9,
        zIndex: 9999,
        pointerEvents: 'none',
        background: '#fff',
        boxShadow: '0 6px 18px rgba(0,0,0,0.25)',
        borderRadius: '6px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        whiteSpace: 'nowrap'
      })
      .appendTo('body');
  });


  $(document).on('touchmove', function (e) {
    if (!touchClone) return;
    e.preventDefault();

    const touch = e.originalEvent.touches[0];

    touchClone.css({
      left: touch.clientX - 50,
      top: touch.clientY - 20
    });
  });

  $(document).on('touchend', function (e) {
    if (!touchDraggedItem || !touchClone) return;

    const touch = e.originalEvent.changedTouches[0];

    // 👇 VERY IMPORTANT: hide clone before detecting target
    touchClone.hide();

    const dropTarget = document.elementFromPoint(
      touch.clientX,
      touch.clientY
    );

    const $dropBox = $(dropTarget).closest('.match-word-box');

    if ($dropBox.length) {
      droppedSuccessfully = true;
      handleDrop($dropBox, touchDraggedItem);
    }

    // cleanup
    touchClone.remove();
    touchClone = null;

    // restore if not dropped
    if (!droppedSuccessfully) {
      touchDraggedItem.show();
    }

    touchDraggedItem = null;
  });


});


function handleDrop($dropBox, draggedElement) {

  var activity = $dropBox.attr("data-choice");
  var daragsInput = $dropBox.attr("data-input");

  daragsInput = $("input[name='" + daragsInput + "']");
  let val = JSON.parse(daragsInput.val());

  var tooltipText = draggedElement.html();
  var answer = draggedElement.attr("data-answer");

  val[activity] = answer;
  daragsInput.val(JSON.stringify(val));

  var tooltip = $('<span class="tooltip"><span class="close-btn">×</span>' + tooltipText + '</span>');

  if (!$dropBox.children('.tooltip').length) {

    tooltip.find('.close-btn').on('click', function () {
      $(this).parent('.tooltip').remove();
      draggedElement.show();

      delete val[activity];
      daragsInput.val(JSON.stringify(val));
    });

    $dropBox.append(tooltip);
    draggedElement.hide();
  } else {
    $dropBox.addClass('not-allowed-drop');
    setTimeout(() => $dropBox.removeClass('not-allowed-drop'), 800);
  }
}
*/

// ==========================draggable-options
// =====================================timer
// document.addEventListener('DOMContentLoaded', function () {
//   const circle = document.querySelector('.progress-ring__circle');
//   if (circle) {
//     const radius = circle.r.baseVal.value;
//     // The rest of your code that uses 'circle'
//     const circumference = 2 * Math.PI * radius;
//     circle.style.strokeDasharray = `${circumference} ${circumference}`;
//     circle.style.strokeDashoffset = circumference;

//     let timeInSeconds = 40 * 60;
//     let timerInterval;

//     function startTimer() {
//       timerInterval = setInterval(updateTimer, 1000);
//     }

//     function updateTimer() {
//       const minutes = Math.floor(timeInSeconds / 60);
//       let seconds = timeInSeconds % 60;

//       const countdownText = document.querySelector('.countdown-text');
//       countdownText.textContent = `${minutes.toString().padStart(2, '0')}:${seconds
//         .toString()
//         .padStart(2, '0')}`;

//       const progress = circumference - (timeInSeconds / (40 * 60)) * circumference;
//       circle.style.strokeDashoffset = progress;

//       if (timeInSeconds <= 0) {
//         clearInterval(timerInterval);
//         countdownText.textContent = '00:00';
//         // Perform any action upon countdown completion
//         // For example: alert('Countdown finished!');
//       } else {
//         timeInSeconds--;
//       }
//     }

//     startTimer();
//   }
// });


document.addEventListener('DOMContentLoaded', () => {
  let currentRecorder = null;

  const setupRecording = (questionId, duration) => {
    const toggleButton = document.getElementById("toggleButton" + questionId);
    const audioElement = document.getElementById("audioElement" + questionId);
    const audioButton = document.getElementById("audioButton" + questionId);

    let mediaRecorder;
    let mediaStream;
    let recordedMimeType = '';
    let recordedChunks = [];
    let isRecording = false;
    let autoStopTimeout;
    let countdownInterval;
    let audioObjectUrl = null;

    if (!toggleButton || !audioElement) return;

    toggleButton.addEventListener('click', toggleRecording);

    function swalOrAlert(title, message, icon) {
      if (window.Swal && typeof Swal.fire === 'function') {
        return Swal.fire({
          title,
          html: message,
          icon: icon || 'info',
          allowOutsideClick: false,
          allowEscapeKey: true,
        });
      }

      alert(title + "\n\n" + message.replace(/<[^>]+>/g, ''));
      return Promise.resolve();
    }

    function formatTime(seconds) {
      const minutes = Math.floor(seconds / 60);
      const secs = seconds % 60;
      return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    function stopAndClearAudio() {
      try {
        audioElement.pause();
        audioElement.currentTime = 0;
      } catch (e) {
        // ignore
      }

      if (audioObjectUrl) {
        try { URL.revokeObjectURL(audioObjectUrl); } catch (e) { /* ignore */ }
        audioObjectUrl = null;
      }

      audioElement.removeAttribute('src');
      try { audioElement.load(); } catch (e) { /* ignore */ }
    }

    async function toggleRecording() {
      if (!isRecording) {
        // ===== Safety checks (iOS / Safari friendly) =====
        if (!window.isSecureContext) {
          await swalOrAlert(
            'Microphone blocked',
            'Audio recording requires HTTPS (secure context). Open this page over HTTPS or test on localhost.',
            'error'
          );
          return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
          await swalOrAlert(
            'Unsupported',
            'This browser does not support microphone access (getUserMedia).',
            'error'
          );
          return;
        }

        if (!window.MediaRecorder) {
          await swalOrAlert(
            'Unsupported',
            'Audio recording is not supported on this browser (MediaRecorder unavailable).',
            'error'
          );
          return;
        }

        if (currentRecorder) {
          await swalOrAlert(
            'Recording in progress',
            'Please stop the other recording first.',
            'warning'
          );
          return;
        }

        recordedChunks = [];

        // If user re-records while previous audio exists, stop it.
        stopAndClearAudio();

        audioElement.style.display = 'none';
        audioButton.style.display = 'none';
        toggleButton.innerHTML = 'Click Here to Stop Recording';

        try {
          await startRecording();
          isRecording = true;

          let timeRemainingSeconds = Math.floor(duration / 1000);

          if (window.Swal && typeof Swal.fire === 'function') {
            Swal.fire({
              title: 'Recording in progress',
              html: `
                <p class="mb-2">Please speak clearly.</p>
                <div style="font-size: 22px; font-weight: 700; font-family: monospace;">
                  Time Limit : <span id="recording-timer">${formatTime(timeRemainingSeconds)}</span>
                </div>
                <p class="mt-2 mb-0">Click below when finished.</p>
              `,
              icon: 'info',
              confirmButtonText: 'Stop recording',
              allowOutsideClick: false,
              allowEscapeKey: false,
              allowEnterKey: false,
              didOpen: () => {
                try { Swal.getConfirmButton()?.focus(); } catch (e) { /* ignore */ }
              }
            }).then(result => {
              if (result.isConfirmed && isRecording) {
                stopRecording();
              }
            });
          }

          clearInterval(countdownInterval);
          countdownInterval = setInterval(() => {
            timeRemainingSeconds--;
            const timerEl = document.getElementById('recording-timer');
            if (timerEl) {
              timerEl.textContent = formatTime(Math.max(0, timeRemainingSeconds));
            }
            if (timeRemainingSeconds <= 0) {
              clearInterval(countdownInterval);
            }
          }, 1000);

          autoStopTimeout = setTimeout(() => {
            if (isRecording) {
              stopRecording();
            }
            if (window.Swal?.isVisible?.()) {
              Swal.close();
            }
          }, duration);

        } catch (err) {
          handlePermissionError(err);
          resetUI();
        }

      } else {
        stopRecording();
      }
    }

    async function startRecording() {
      mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });

      // Safari-safe MIME selection
      const mimeCandidates = [
        'audio/mp4;codecs=mp4a.40.2',
        'audio/mp4',
        'audio/webm;codecs=opus',
        'audio/webm',
        ''
      ];

      for (const type of mimeCandidates) {
        if (!type || (MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(type))) {
          recordedMimeType = type;
          break;
        }
      }

      const options = recordedMimeType ? { mimeType: recordedMimeType } : {};
      mediaRecorder = new MediaRecorder(mediaStream, options);

      mediaRecorder.ondataavailable = e => recordedChunks.push(e.data);
      mediaRecorder.onstop = handleRecordingStop;
      mediaRecorder.onerror = e => console.error('Recorder error:', e);

      mediaRecorder.start();
      currentRecorder = mediaRecorder;
    }

    function stopRecording() {
      if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
      }

      cleanup();
      resetUI();

      if (Swal.isVisible()) {
        Swal.close();
      }

    }

    function cleanup() {
      if (mediaStream) {
        mediaStream.getTracks().forEach(t => t.stop());
        mediaStream = null;
      }
      clearTimeout(autoStopTimeout);
      clearInterval(countdownInterval);
      isRecording = false;
      currentRecorder = null;
    }

    function resetUI() {
      toggleButton.innerHTML = '<i class="fas fa-play"></i> Start Recording';
    }

    function handleRecordingStop() {
      const blobType =
        recordedMimeType ||
        recordedChunks[0]?.type ||
        'audio/webm';

      const blob = new Blob(recordedChunks, { type: blobType });
      recordedChunks = [];

      setExamData(questionId, blob, blobType);

      // Replace any previous blob URL.
      stopAndClearAudio();
      audioObjectUrl = URL.createObjectURL(blob);
      audioElement.src = audioObjectUrl;
      audioElement.style.display = 'block';
      audioButton.style.display = 'block';

      audioButton.onclick = () => {
        unsetExamData(questionId);
        stopAndClearAudio();
        audioElement.style.display = 'none';
        audioButton.style.display = 'none';
        toggleButton.style.display = 'block';
      };
    }

    function handlePermissionError(err) {
      if (err?.name === 'NotAllowedError') {
        swalOrAlert(
          'Microphone Permission Needed',
          `<div style="text-align:left">
             <p><b>Microphone permission is blocked.</b></p>
             <p>Fix it then reload the page:</p>
             <ul style="margin:0; padding-left:18px">
               <li>iPhone/iPad Safari: Address bar → Website Settings → Microphone → <b>Allow</b></li>
               <li>Chrome/Edge: lock/sliders icon → Site settings → Microphone → <b>Allow</b></li>
             </ul>
           </div>`,
          'warning'
        );
      } else {
        swalOrAlert('Unable to access microphone', 'Microphone access denied, blocked (HTTP), or unsupported on this device/browser.', 'error');
      }
      console.error(err);
    }
  };

  function setExamData(index, blob, mimeType) {
    const reader = new FileReader();
    reader.readAsDataURL(blob);
    reader.onloadend = () => {
      const base64 = reader.result.split(',')[1];
      $(`#speaking_exam input[name='answer[${index}][answer]'], #device_test_form input[name='answer[${index}][answer]']`).val(base64);

      // Save MIME for Safari/iOS backend handling
      let mimeInput = $(`#speaking_exam input[name='answer[${index}][mime]'], #device_test_form input[name='answer[${index}][mime]']`);
      if (!mimeInput.length) {
        // Try to append to the correct form based on what exists
        let targetForm = $('#speaking_exam').length ? '#speaking_exam' : '#device_test_form';
        mimeInput = $('<input>', {
          type: 'hidden',
          name: `answer[${index}][mime]`
        }).appendTo(targetForm);
      }
      mimeInput.val(mimeType || '');
    };
  }

  function unsetExamData(index) {
    $(`#speaking_exam input[name='answer[${index}][answer]'], #device_test_form input[name='answer[${index}][answer]']`).val('');
  }

  // Init
  document.querySelectorAll('.record-container').forEach(el => {
    const index = el.getAttribute('data-index');
    setupRecording(index, 600000); // 10 minutes
  });
});











// // ==========================word-dragging - OLD DRAG & DROP CODE (COMMENTED OUT)
// document.addEventListener("DOMContentLoaded", function () {
//   ... [old code removed for brevity]
// });

// ==========================word-clicking (NEW CLICK-BASED SYSTEM)
document.addEventListener("DOMContentLoaded", () => {

  /* =========================
     HELPER FUNCTIONS FOR REARRANGE
  ========================== */
  function addRemoveButtonToWord(word, pool, dropzone, input) {
    if (word.querySelector(".remove-word-btn")) return;

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "remove-word-btn";
    btn.innerHTML = '<i class="fa-solid fa-times"></i>';
    btn.title = "Remove";

    btn.addEventListener("click", e => {
      e.stopPropagation();
      btn.remove();
      word.classList.remove('in-dropzone');
      pool.appendChild(word);
      updateRearrangeAnswer(dropzone, input);
    });

    word.appendChild(btn);
  }

  function updateRearrangeAnswer(dropzone, input) {
    const words = Array.from(dropzone.querySelectorAll(".word"))
      .map(w => {
        const textNode = Array.from(w.childNodes).find(node => node.nodeType === Node.TEXT_NODE);
        return textNode ? textNode.textContent.trim() : '';
      });

    input.value = JSON.stringify(words);

    // Manage placeholder visibility
    const placeholder = dropzone.querySelector('.dropzone-placeholder');
    if (placeholder) {
      placeholder.style.display = words.length > 0 ? 'none' : 'block';
    }
  }

  /* =========================
     CLICK-BASED REARRANGE LOGIC
  ========================== */
  document.querySelectorAll(".clickable-word").forEach(word => {
    word.addEventListener("click", function(e) {
      e.preventDefault();
      
      // Find the quiz box and related elements
      const quizBox = word.closest(".quiz-box");
      const pool = quizBox.querySelector(".wordContainer");
      const dropzone = quizBox.querySelector(".dropzone");
      const input = quizBox.querySelector("input[type='hidden']");
      
      // Validate that word belongs to this question
      const wordQuestionId = word.getAttribute('data-question-id');
      const dropzoneQuestionId = dropzone.getAttribute('data-question-id');

      if (wordQuestionId !== dropzoneQuestionId) return;

      // Only allow clicking words that are still in the pool
      if (!word.classList.contains('in-dropzone')) {
        word.classList.add('in-dropzone');
        addRemoveButtonToWord(word, pool, dropzone, input);
        dropzone.appendChild(word);
        updateRearrangeAnswer(dropzone, input);
      }
    });
  });

});





function customSerialize(arr) {
  let result = 'a:' + arr.length + ':{';
  arr.forEach((value, index) => {
    if (typeof value === 'string') {
      result += 'i:' + index + ';s:' + value.length + ':"' + value + '";';
    } else if (typeof value === 'number') {
      result += 'i:' + index + ';i:' + value + ';';
    }
    // Add more cases for other data types if needed
  });
  result += '}';
  return result;
}


// ==========================word-dragging
// =============================img-uplaod
document.addEventListener('DOMContentLoaded', () => {
  document.addEventListener("change", function (e) {
    if (e.target.classList.contains("writing_file")) {


      var typing = e.target.getAttribute("name");
      var typingTextarea = document.querySelector(".upload-img textarea[name='" + typing + "']");
      if (typingTextarea) {
        typingTextarea.value = null;
      }

      var pr = e.target.getAttribute("data-prev");
      var preview = document.getElementById(pr);
      var errorMessage = e.target.closest("div").querySelector(".text-danger");

      var files = e.target.files;
      var currentIndex = 0;

      preview.innerHTML = '';
      errorMessage.textContent = ''; // Clear any previous error message

      processFiles();

      function processFiles() {
        if (currentIndex < files.length) {
          var file = files[currentIndex];

          if (file.type.match('image/*')) {
            var reader = new FileReader();

            reader.onload = function (event) {
              var img = document.createElement("img");
              img.src = event.target.result;
              img.classList.add("preview");
              img.classList.add("mx-2");
              img.classList.add("rounded");
              img.width = 200;
              img.height = 300;
              preview.appendChild(img);

              // Process the next file
              currentIndex++;
              processFiles();
            };

            reader.readAsDataURL(file);
          } else {
            errorMessage.textContent = 'Please select valid image files (PNG or JPEG).';
            e.target.value = null;
            preview.innerHTML = ''; // Clear the preview
          }
        } else {
          if (files.length > 10) {
            errorMessage.textContent = 'Please select 10 or fewer files (PNG or JPEG).';
            e.target.value = null;
            preview.innerHTML = ''; // Clear the preview
          }
        }
      }
    }
  });

  $(document).ready(function () {
    $(document).on("click", ".toogleMethod", function () {
      var index = $(this).attr("data-index");
      $("#fileInput_" + index).val(null);
      $("#imagePreview_" + index).html(``);
    })
  });



});

// =============================img-uplaod
// =============================keybaord
var VKeyboard = function (clientOptions) {
  if (clientOptions && typeof clientOptions !== 'object') {
    console.error('Keybaord accept an object of supprted keybaord options.');
  }


  var keys = {
    'chr': [
      [1, 2, 3, 4, 5, 6, 7, 8, 9, 0, "x8"],
      ["@", "#", "$", "%", "^", "&", "*", "(", ")", "-", "_", "=", "+"],
      ["[", "]", "{", "}", ";", ":", "|", "/", "<", ">", "'", "x13"],
      ["", "", "", "?", ".", "`", "~", "!", ",", "", "", "",],
      ["xlang", "x32"]
    ],
    'ar': [
      [1, 2, 3, 4, 5, 6, 7, 8, 9, 0, "x8"],
      ["ض", "ص", "ث", "ق", "ف", "غ", "ع", "ه", "خ", "ح", "ج", "?"],
      ["ش", "س", "ي", "ب", "ل", "ا", "ت", "ن", "م", "ک", "ط", "."],
      ["ئ", "ء", "ؤ", "ر", "لا", "ى", "ة", "و", "ز", "ظ", "د", ","],
      ["xlang", "x32", "x13"]
    ]
  },
    supportedLanguages = Object.keys(keys), // TODO Object.keys es5
    rtlLanguages = ['ar'],
    options = {
      lang: 'fr',
      charsOnly: false,
      caps: false
    },
    elements = {
      container: null,
      keysContainer: null,
      keyboardInput: null,
      clientInput: null,
    },
    BACKSPACE = 'x8',
    CAPS_LOCK = 'x20',
    RETURN = 'x13',
    SPACE = 'x32',
    LANG = 'xlang',
    DONE = 'xdone';

  if (clientOptions.lang && supportedLanguages.indexOf(clientOptions.lang) === -1) {
    console.error(lang + ' language is not supported!');
  }

  // TODO assign es5
  Object.assign(options, clientOptions);

  // Simple helper for DOM manipulation
  var Helper = (function (selector) {

    if (!(this instanceof Helper)) {
      return new Helper(selector);
    }

    this.length = 0;

    if (typeof selector === 'string') {
      var self = this;
      var eles = document.querySelectorAll(selector);
      this.length = eles.length;
      eles.forEach(function (ele, i) {
        self[i] = ele;
      });
    }

    if (selector instanceof Node || selector === window) {
      this[0] = selector;
    }

    if (selector.constructor && selector.constructor.name === 'Array') {
      var arr = selector,
        i = arr.length - 1;
      while (i >= 0) {
        this[i] = arr[i];
        this.length++;
        i--;
      }
    }

    Helper.prototype.length = 0;

    Helper.prototype.addClass = function (classes) {
      var self = this;
      classes.split(' ').forEach(function (cls) {
        self[0].classList.add(cls);
      });
      return this;
    };

    Helper.prototype.removeClass = function (classes) {
      var self = this;
      classes.split(' ').forEach(function (cls) {
        self[0].classList.remove(cls);
      });
      return this;
    };

    Helper.prototype.toggle = function (cls) {
      return this[0].classList.toggle(cls);
    };

    Helper.prototype.contains = function (cls) {
      return this[0].classList.contains(cls);
    };

    Helper.prototype.appendTo = function (appendTo) {
      document.querySelector(appendTo).appendChild(this[0]);
      return this;
    };

    Helper.prototype.get = function (index) {
      return this[0];
    };

    Helper.prototype.setAttribute = function (name, val) {
      this[0].setAttribute(name, val);
      return this;
    };

    Helper.prototype.text = function (text) {
      this[0].textContent = text;
      return this;
    };

    Helper.prototype.on = function (event, handler) {
      this[0].addEventListener(event, handler);
      return this;
    };

    Helper.prototype.splice = Array.prototype.splice;
    Helper.prototype.each = Array.prototype.forEach;
  });

  Helper.create = function (tag) {
    var ele = document.createElement(tag);
    return Helper(ele);
  };

  function renderUI(renderOptions) {
    var charsOnly = renderOptions.charsOnly,
      capsLock = renderOptions.caps,
      container = elements.container = Helper
        .create('div')
        .addClass('keyboard keyboard--hidden')
        .appendTo('body')
        .get(0),
      keysContainer = elements.keysContainer = Helper
        .create('div')
        .addClass('keyboard__keys')
        .appendTo('.keyboard')
        .get(0),
      input = elements.keyboardInput = Helper
        .create('textarea')
        .addClass('keyboard__input')
        .get(0);

    keysContainer.insertBefore(input, keysContainer.firstElementChild);
    Helper.create('br').appendTo('.keyboard__keys');

    operateOnKeys(options.lang, function (key) {

      if (isSpecialKey(key)) { // Handle special keys
        switch (key) {
          case BACKSPACE:
            var btn = Helper.create('button')
              .addClass('keyboard__key keyboard__key--wide')
              .setAttribute('type', 'button')
              .get(0),
              icon = Helper.create('i')
                .addClass('icon material-icons')
                .text('backspace')
                .get(0);

            btn.appendChild(icon);
            keysContainer.appendChild(btn);

            Helper(btn).on('click', function () {
              elements.keyboardInput.value = elements.keyboardInput.value.slice(0, -1);
              elements.clientInput.value = elements.clientInput.value.slice(0, -1);
            });

            break;
          case CAPS_LOCK:
            var btn = Helper.create('button')
              .addClass('keyboard__key keyboard__key--wide keyboard__key--activatable')
              .setAttribute('type', 'button')
              .get(0),
              icon = Helper.create('i')
                .addClass('icon material-icons')
                .text('keyboard_capslock')
                .get(0);

            btn.appendChild(icon);
            keysContainer.appendChild(btn);

            if (capsLock) {
              Helper(btn).addClass('keyboard__key--active');
              toggleCapsLock();
            }

            Helper(btn).on('click', function () {
              options.caps = !options.caps;
              this.classList.toggle('keyboard__key--active');
              toggleCapsLock();
            });

            break;
          case RETURN:
            var btn = Helper.create('button')
              .addClass('keyboard__key keyboard__key--wide keyboard__key--close')
              .setAttribute('type', 'button')
              .get(0),
              icon = Helper.create('i')
                .addClass('icon material-icons')
                .text('close')
                .get(0);

            btn.appendChild(icon);
            keysContainer.appendChild(btn);
            //     break;
            // case DONE:
            //     var btn = Helper.create('button')
            //         .addClass('keyboard__key keyboard__key--wide keyboard__key--dark')
            //         .setAttribute('type', 'button')
            //         .get(0),
            //         icon = Helper.create('i')
            //         .addClass('icon material-icons')
            //         .text('check_circle')
            //         .get(0);

            btn.appendChild(icon);
            keysContainer.appendChild(btn);

            Helper(btn).on('click', done);
            break;
          case LANG:
            var btn = Helper.create('button')
              .addClass('keyboard__key keyboard__key--wide keyboard__key--dark keyboard__langauge__dropdown')
              .setAttribute('type', 'button')
              .get(0),
              icon = Helper.create('i')
                .addClass('icon material-icons')
                .text('language')
                .get(0);

            btn.appendChild(icon);
            keysContainer.appendChild(btn);

            var list = Helper.create('ul')
              .addClass('languages__list')
              .get(0);

            var items = [];
            for (var i = 0; i < supportedLanguages.length; i++) {
              var lang = supportedLanguages[i];
              var li = Helper.create('li')
                .addClass('language__item')
                .setAttribute('data-lang', lang)
                .text(lang)
                .get(0);
              list.appendChild(li);
              items.push(li);
            }

            btn.appendChild(list);

            // Toggle the language list 
            Helper(btn).on('click', function () {
              Helper(list).toggle('show');
            });

            // Binding click event on each langauge list item
            Helper(items).each(function (item) {
              Helper(item).on('click', function (e) {
                e.stopPropagation(); // Stop event bubbling to the dropdown button
                changeLangauge(this.dataset.lang);
                Helper(list).removeClass('show');
              });
            });
            break;
          case SPACE:
            var btn = Helper.create('button')
              .addClass('keyboard__key keyboard__key--extra-wide')
              .setAttribute('type', 'button')
              .get(0),
              icon = Helper.create('i')
                .addClass('icon material-icons')
                .text('space_bar')
                .get(0);

            btn.appendChild(icon);
            keysContainer.appendChild(btn);

            Helper(btn).on('click', function () {
              elements.keyboardInput.value += ' ';
              elements.clientInput.value += ' ';
            });
            break;
        }
      } else { // Regular keys
        // Handle charsOnly option
        if (charsOnly === true && typeof key === 'number') {
          return;
        }

        var btn = Helper.create('button')
          .addClass('keyboard__key')
          .setAttribute('type', 'button')
          .text(key)
          .get(0);

        keysContainer.appendChild(btn);

        Helper(btn).on('click', function () {
          Helper(elements.keyboardInput)
            .setAttribute('dir', rtlLanguages.indexOf(options.lang) !== -1 ? 'rtl' : 'ltr');
          elements.keyboardInput.value += btn.textContent;
          elements.clientInput.value += btn.textContent;
        });
      }

      // Add new row of keys
      var isBreakLine = arguments[arguments.length - 1];
      if (isBreakLine) {
        keysContainer.appendChild(document.createElement('br'));
      }
    });
  }

  function initKeyboardInput(properties) {
    elements.keyboardInput.placeholder = properties.placeholder;
    elements.keyboardInput.value = properties.value;
  }

  function operateOnKeys(lang, clb) {
    Helper(keys[lang]).each(function (rowKeys, rowIndex) {
      Helper(rowKeys).each(function (key, keyIndex) {
        var isBreakLine = keyIndex === rowKeys.length - 1;
        clb.apply(this, [key, rowIndex, keyIndex, isBreakLine]);
      });
    });
  }

  function toggleCapsLock() {
    Helper('button.keyboard__key').each(function (key) {
      if (key.childElementCount === 0) {
        if (options.caps === true) {
          key.textContent = key.textContent.toUpperCase();
        } else {
          key.textContent = key.textContent.toLowerCase();
        }
      }
    });
  }

  function done() {
    const body = document.body;

    // Close the keyboard regardless of the elements' availability
    Helper(elements.container).addClass('keyboard--hidden');
    Helper('.languages__list').removeClass('show');
    // Perform any other necessary actions when closing the keyboard
    body.style.paddingBottom = '0px';

    // Update client input if elements are available
    if (elements.clientInput && elements.keyboardInput) {
      elements.clientInput.value = elements.keyboardInput.value;
    }
    // else {
    //     console.error('Elements are not available or null.');
    // }
  }

  function changeLangauge(lang) {
    if (supportedLanguages.indexOf(lang) === -1) return;

    var k = Helper('.keyboard__key:not(.keyboard__key--wide):not(.keyboard__key--extra-wide)'),
      i = 0;

    operateOnKeys(lang, function (key) {
      if (!isSpecialKey(key) && k[i]) {
        Helper(k[i]).text(key + "");
        i++;
      }
    });

    options.lang = lang;
  }

  function isSpecialKey(key) {
    return typeof key !== 'number' && key.match(/x\w+/) !== null;
  }

  function initEvents() {
    // Binding the focus event on inputs, textareas (exclude the keyboard input)
    Helper('textarea').each(function (input) {
      Helper(input).on('focus', function (e) {
        var input = e.target;
        // exclude the readonly fileds
        if (input.readOnly) return;
        // Shows the kayboard
        Helper(elements.container).removeClass('keyboard--hidden');
        elements.clientInput = input;
        // Init the keyboard input
        initKeyboardInput({
          placeholder: input.placeholder,
          value: input.value
        });
        // Update the keyboard when typing in an input 
        Helper(input).on('input', function () {
          elements.keyboardInput.value = this.value;
        });
      });
    });

    // Hide the keyboard when clicking out of the keyboard view
    Helper(window).on('click', function (e) {
      if (!Helper(elements.container).contains('keyboard--hidden')) {
        if (e.target !== elements.keyboardInput &&
          ['button', 'button'].indexOf(e.target.tagName.toLowerCase()) === -1 &&
          e.target.closest('.keyboard') !== elements.container) {
          Helper(elements.container).addClass('keyboard--hidden');
          Helper('.languages__list').removeClass('show');
          done();
        }
      }
    });

    // Update the client input when typing in the keyboard input
    Helper(elements.keyboardInput).on('input', function () {
      elements.clientInput.value = this.value;
    });
  }

  return {
    init: function () {
      // rendering keyboard HTML markup
      renderUI({
        charsOnly: options.charsOnly,
        caps: options.caps
      });
      // Apply caps lock if enabled
      if (options.caps) {
        toggleCapsLock();
      }
      // Binding events
      initEvents();
    }
  };
};

document.addEventListener("DOMContentLoaded", function () {
  var keyboard = VKeyboard({
    lang: 'ar',
    charsOnly: false,
    caps: false
  });

  keyboard.init(); // Initialize the keyboard

  var showKeyboardBtns = document.querySelectorAll('.showKeyboardBtn');
  var keyboardContainer = document.querySelector('.keyboard');

  var body = document.body;

  showKeyboardBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (keyboardContainer.classList.contains('keyboard--hidden')) {
        keyboardContainer.classList.remove('keyboard--hidden');
        body.style.paddingBottom = '350px';

        var textarea = this.parentNode.querySelector('textarea'); // Find the textarea within the same parent
        if (textarea) {
          textarea.focus(); // Focus on the textarea

          textarea.setAttribute('dir', 'rtl');
          textarea.style.textAlign = 'right !important';

          textarea.scrollIntoView({
            behavior: "smooth",
            block: "center",
          });
        }
      } else {
        keyboardContainer.classList.add('keyboard--hidden');
        body.style.paddingBottom = '0px';

      }
    });
  });
});

// =============================keybaord

