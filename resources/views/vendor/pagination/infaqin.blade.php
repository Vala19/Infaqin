@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Page navigation') }}" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-ink/60">
            {!! __('Showing :first–:last of :total', [
                'first' => '<span class="font-medium text-night">'.e($paginator->firstItem()).'</span>',
                'last' => '<span class="font-medium text-night">'.e($paginator->lastItem()).'</span>',
                'total' => '<span class="font-medium text-night">'.e($paginator->total()).'</span>',
            ]) !!}
        </p>

        <ul class="flex items-center gap-1">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="grid size-10 place-items-center rounded-lg text-ink/30" aria-disabled="true" aria-label="{{ __('Previous') }}"><x-icon name="chevron-left" class="size-5" /></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid size-10 place-items-center rounded-lg text-ink hover:bg-white hover:text-brand" aria-label="{{ __('Previous') }}"><x-icon name="chevron-left" class="size-5" /></a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="grid size-10 place-items-center text-ink/50">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="grid size-10 place-items-center rounded-lg bg-brand text-sm font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="grid size-10 place-items-center rounded-lg text-sm text-ink hover:bg-white hover:text-brand" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid size-10 place-items-center rounded-lg text-ink hover:bg-white hover:text-brand" aria-label="{{ __('Next') }}"><x-icon name="chevron-right" class="size-5" /></a>
                @else
                    <span class="grid size-10 place-items-center rounded-lg text-ink/30" aria-disabled="true" aria-label="{{ __('Next') }}"><x-icon name="chevron-right" class="size-5" /></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
