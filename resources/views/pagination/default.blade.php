@if($paginator->hasPages())
    <nav class="pagination-bar" aria-label="Paginación">
        <p class="pagination-summary">Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} @if(method_exists($paginator, 'total')) de {{ $paginator->total() }} registros @endif</p>
        <ul class="pagination">
            <li>@if($paginator->onFirstPage())<span class="page-link" aria-disabled="true">Anterior</span>@else<a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>@endif</li>
            @foreach($elements ?? [] as $element)
                @if(is_string($element))<li class="page-number"><span class="page-link" aria-hidden="true">{{ $element }}</span></li>@endif
                @if(is_array($element))@foreach($element as $page => $url)<li class="page-number">@if($page == $paginator->currentPage())<span class="page-link is-current" aria-current="page" aria-label="Página {{ $page }}">{{ $page }}</span>@else<a class="page-link" href="{{ $url }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>@endif</li>@endforeach @endif
            @endforeach
            <li>@if($paginator->hasMorePages())<a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>@else<span class="page-link" aria-disabled="true">Siguiente</span>@endif</li>
        </ul>
    </nav>
@endif
