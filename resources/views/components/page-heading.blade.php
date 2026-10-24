@props(['title', 'description' => null, 'back' => null])
<div class="page-heading"><div><a class="back-link" href="{{ $back ?? route('home') }}"><x-icon name="arrow-left" />{{ $back ? 'Volver' : 'Todos los módulos' }}</a><h1>{{ $title }}</h1>@if($description)<p class="text-muted">{{ $description }}</p>@endif</div>@if($slot->isNotEmpty())<div class="heading-actions">{{ $slot }}</div>@endif</div>
