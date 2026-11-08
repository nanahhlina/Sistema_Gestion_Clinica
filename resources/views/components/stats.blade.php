@props(['items' => []])
@if(count($items))<dl class="stats-grid" aria-label="Resumen">@foreach($items as $label => $number)<div class="stat-card"><dt>{{ $label }}</dt><dd>{{ is_numeric($number) ? number_format((float) $number, 0, ',', '.') : $number }}</dd></div>@endforeach</dl>@endif
