@props(['title', 'breadcrumbs' => []])

<div class="page-header mb-4">
    <h1 class="mb-1">{{ $title }}</h1>
    @if (count($breadcrumbs))
        <ol class="breadcrumb mb-0">
            @foreach ($breadcrumbs as $crumb)
                @if ($loop->last || empty($crumb['url'] ?? null))
                    <li class="breadcrumb-item active">{{ $crumb['label'] }}</li>
                @else
                    <li class="breadcrumb-item">
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    </li>
                @endif
            @endforeach
        </ol>
    @endif
</div>
