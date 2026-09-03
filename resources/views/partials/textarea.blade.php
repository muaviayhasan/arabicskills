<h6>{{ $label }}</h6>
<textarea wire:ignore name="activity[activity]" class="form-control summernote_bank" rows="4">{{ $value ?? '' }}
</textarea>
<span id="activity.activity" class="ms-2 text-danger"></span>
