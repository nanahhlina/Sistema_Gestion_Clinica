@props(['title' => 'No hay registros', 'message' => 'Los registros aparecerán aquí cuando estén disponibles.', 'icon' => 'file'])
<div {{ $attributes->class(['empty-state']) }}><span class="empty-icon"><x-icon :name="$icon" /></span><h2>{{ $title }}</h2><p>{{ $message }}</p>{{ $slot }}</div>
