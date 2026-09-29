@props(['title' => null, 'remainingSeconds' => null])

{{-- The same header, with the exam countdown. --}}
<x-student-header :title="$title" :remainingSeconds="$remainingSeconds" />
