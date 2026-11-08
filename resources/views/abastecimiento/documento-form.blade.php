@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title',$title)
@section('content')
<x-page-heading :title="$title" :back="route('home')" />
@unless($purchase)<form method="get" class="filters"><x-field name="q" label="Buscar paciente por nombre o documento" :value="request('q')" /><button class="button">Buscar paciente</button></form>@endunless
<form method="post" action="{{ $action }}" class="form-panel">@csrf
<input type="hidden" name="clave" value="{{ $key }}"><input type="hidden" name="firma" value="{{ \App\Modules\Shared\Services\OperacionWeb::signature(auth()->user(),$operation,$key) }}">
@if($purchase)
<x-field name="proveedor_id" label="Proveedor activo" type="select" :options="$suppliers" :value="$values['proveedor_id'] ?? ''" />
@else
<x-field name="paciente_id" label="Paciente activo" type="select" :options="$patients" :value="$values['paciente_id'] ?? ''" />
@endif
@foreach($lines as $i=>$line)
<fieldset><legend>Producto {{ $i+1 }}</legend>
<x-field :name="'lineas['.$i.'][producto_id]'" label="Producto" type="select" :options="$options" :value="$line['producto_id'] ?? ''" />
<x-field :name="'lineas['.$i.'][cantidad]'" label="Cantidad entera" :value="$line['cantidad'] ?? ''" inputmode="numeric" />
@if($purchase)<x-field :name="'lineas['.$i.'][precio_unitario]'" label="Precio unitario BOB" :value="$line['precio_unitario'] ?? ''" inputmode="decimal" />@endif
</fieldset>
@endforeach
@unless($purchase)
<x-field name="metodo" label="Método de pago" type="select" :options="['Efectivo'=>'Efectivo','PayPal'=>'PayPal Sandbox']" :value="$values['metodo'] ?? 'Efectivo'" />
<x-field name="recibido" label="Efectivo recibido BOB (solo efectivo)" :value="$values['recibido'] ?? ''" />
@endunless
<p>Las líneas vacías se omiten. El servidor calcula los importes al revisar y confirmar.</p>
<button class="button" name="paso" value="agregar" formnovalidate>Agregar otra línea</button>
<button class="button button-primary" name="paso" value="revisar">Revisar</button>
</form>
@endsection
