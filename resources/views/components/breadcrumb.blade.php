@props(['page_title', 'breadcrumb'])
<!-- start page title -->
<div class="row justify-content-center align-items-center g-2 mb-5">
    <div class="col-md-6">
        <div class="page-title-box align-self-center">

            <h4 class="page-title mb-0">{{ $page_title ?? '' }}</h4>
        </div>
    </div>
    <div class="col-md-6 align-items-center">
        @if ($breadcrumb)
            <nav class="float-end mt-3">
                <ol class="breadcrumb">
                    @foreach ($breadcrumb as $label => $url)
                        @if ($loop->last)
                            <li class="breadcrumb-item active">{{ $label }}</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>
</div>

<!-- end page title -->
