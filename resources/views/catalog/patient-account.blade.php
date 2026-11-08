@extends('layouts.app')
@section('title', 'Vínculo con paciente')
@section('content')
    <x-page-heading title="Vínculo con paciente" :description="'Cuenta: '.$account->nombre_usuario.' · '.$account->display_name" :back="route('catalog.edit', ['module' => 'usuarios', 'id' => $account->id])" />
    <section class="panel form-panel">
        <form class="form-stack" method="POST" action="{{ route('catalog.users.patient.update', $account->id) }}" data-submit-form>
            @csrf
            @method('PUT')
            <p>Comprueba el documento y la fecha de nacimiento antes de asociar la cuenta. El vínculo identifica a la persona que podrá consultar sus propios datos.</p>
            <x-field name="paciente_id" label="Paciente asociado" type="select" :options="['' => 'Sin vínculo con paciente'] + $patients" :value="$account->paciente_id" hint="Se muestran pacientes disponibles y el vínculo actual. Selecciona Sin vínculo con paciente para retirarlo." />
            @if($account->paciente_verificado_en)
                <p class="text-muted">Vínculo verificado el {{ $account->paciente_verificado_en->timezone('America/La_Paz')->format('d/m/Y H:i') }}.</p>
            @endif
            <x-field name="confirmar_vinculo" type="checkbox" label="He verificado la identidad y confirmo el vínculo seleccionado o su retirada." :value="false" :required="true" />
            <div class="form-actions">
                <button class="button button-primary" type="submit">Guardar vínculo</button>
                <a class="button" href="{{ route('catalog.edit', ['module' => 'usuarios', 'id' => $account->id]) }}">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
