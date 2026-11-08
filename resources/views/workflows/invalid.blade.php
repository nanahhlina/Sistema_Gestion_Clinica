@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title', 'Revisa la operación')
@section('content')
<x-page-heading title="Revisa la operación" :back="route('home')" />
<div class="alert alert-danger" role="alert"><ul>@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul></div>
<p>Regresa al formulario para corregir los datos. No se guardó la operación.</p>
<button type="button" class="button" onclick="history.back()">Volver al formulario</button>
<noscript><p>Usa el botón Atrás del navegador para recuperar el formulario.</p></noscript>
@endsection
