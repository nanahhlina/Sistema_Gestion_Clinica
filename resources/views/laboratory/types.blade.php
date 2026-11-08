@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Tipos de análisis')
@section('content')
<x-page-heading title="Tipos de análisis" :back="route('home')" />
<a class="button button-primary" href="{{ route('analysis-types.create') }}">Nuevo tipo de análisis</a>
@forelse($rows as $row)<article class="table-panel"><h2>{{ $row->nombre }}</h2><p>{{ $row->descripcion }} · {{ $row->activo?'Activo':'Inactivo' }}</p><a class="button" href="{{ route('analysis-types.edit',$row->id) }}">Editar</a></article>@empty<p>No hay tipos de análisis registrados.</p>@endforelse
{{ $rows->links('pagination.default') }}
@endsection
