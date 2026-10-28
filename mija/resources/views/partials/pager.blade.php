@if($paginator->hasPages())
<div class="pager">
  @if($paginator->onFirstPage())<span class="dis">›</span>@else<a href="{{ $paginator->previousPageUrl() }}">›</a>@endif
  @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
    <a href="{{ $url }}" class="{{ $page == $paginator->currentPage() ? 'active' : '' }}">{{ fa($page) }}</a>
  @endforeach
  @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}">‹</a>@else<span class="dis">‹</span>@endif
</div>
@endif
