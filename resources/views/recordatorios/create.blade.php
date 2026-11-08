@extends('layouts.app')
@section('body-class', 'app-page workflow-page')
@section('title', 'Registrar recordatorio')
@section('content')
<x-page-heading title="Registrar recordatorio" description="Configura un recordatorio de cita médica para el paciente y/o el médico." :back="route('reminders.index')" />

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <x-icon name="alert" />
        <div>
            <strong>Revisa los siguientes campos:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- Paso 4: Buscar y seleccionar paciente --}}
<section class="table-panel" style="padding: 20px; margin-bottom: 24px;">
    <h2>1. Seleccionar paciente</h2>
    <form method="GET" action="{{ route('reminders.create') }}" class="filters" style="margin-bottom: 16px;">
        <div style="display: flex; gap: 10px; align-items: flex-end;">
            <div style="flex: 1;">
                <label for="q" style="font-weight: 600; display: block; margin-bottom: 6px;">Buscar paciente por nombre o CI:</label>
                <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Ej: Ana o TEST-001" style="width: 100%; min-height: 42px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
            </div>
            <button type="submit" class="button button-primary"><x-icon name="search" /> Buscar</button>
            @if($search)
                <a class="button" href="{{ route('reminders.create') }}">Limpiar</a>
            @endif
        </div>
    </form>

    <form method="GET" action="{{ route('reminders.create') }}">
        @if($search)<input type="hidden" name="q" value="{{ $search }}">@endif
        <div style="display: flex; gap: 10px; align-items: flex-end;">
            <div style="flex: 1;">
                <x-field name="paciente_id" label="Pacientes encontrados" type="select" :options="['' => 'Selecciona un paciente...'] + $patients" :value="$patientId" onchange="this.form.submit()" required />
            </div>
            <noscript>
                <button type="submit" class="button">Elegir paciente</button>
            </noscript>
        </div>
    </form>
</section>

{{-- Paso 5: Seleccionar cita médica del paciente --}}
@if($selectedPatient)
    <section class="table-panel" style="padding: 20px; margin-bottom: 24px;">
        <h2>2. Seleccionar cita médica asociada</h2>
        <p><strong>Paciente seleccionado:</strong> {{ $selectedPatient->nombres }} {{ $selectedPatient->apellidos }} (CI: {{ $selectedPatient->ci ?: 'Sin CI' }}) · Tel: {{ $selectedPatient->telefono }} · Correo: {{ $selectedPatient->correo ?: 'Sin correo registrado' }}</p>

        @if(empty($patientAppointments))
            <div class="alert alert-warning">
                <x-icon name="alert" />
                <div>
                    <strong>El paciente no tiene citas activas programadas.</strong>
                    <p style="margin: 4px 0 0;">Solo se pueden programar recordatorios para citas en estado <em>Programada</em> o <em>Confirmada</em>.</p>
                </div>
            </div>
            <a class="button button-primary" href="{{ route('appointments.create', ['paciente_id' => $selectedPatient->id]) }}">
                <x-icon name="plus" /> Agendar nueva cita médica
            </a>
        @else
            <form method="GET" action="{{ route('reminders.create') }}" style="margin-top: 14px;">
                <input type="hidden" name="paciente_id" value="{{ $selectedPatient->id }}">
                @if($search)<input type="hidden" name="q" value="{{ $search }}">@endif
                <div style="display: flex; gap: 10px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label for="select-cita" style="font-weight: 600; display: block; margin-bottom: 6px;">Citas activas del paciente:</label>
                        <select id="select-cita" name="cita_id" onchange="this.form.submit()" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
                            @foreach($patientAppointments as $app)
                                <option value="{{ $app->id }}" {{ ($selectedCita && $selectedCita->id == $app->id) ? 'selected' : '' }}>
                                    {{ $app->codigo }} · {{ \App\Modules\Shared\Clinic::date($app->inicio)->format('d/m/Y H:i') }} · {{ $app->especialidad }} (Dr. {{ $app->medico_nombre }}) [{{ $app->estado }}]
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <noscript><button type="submit" class="button">Elegir cita</button></noscript>
                </div>
            </form>
        @endif
    </section>
@endif

{{-- Paso 6 y 7: Mostrar datos de la cita y configurar recordatorio --}}
@if($selectedCita)
    <section class="table-panel" style="padding: 20px; margin-bottom: 24px; background: #fafcfe; border-color: #cbdff6;">
        <h2 style="color: var(--blue-dark); margin-bottom: 12px;">3. Datos de la cita médica</h2>
        <dl style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin: 0;">
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Código de cita</dt>
                <dd style="font-size: 1.1rem; font-weight: bold; margin: 4px 0 0;"><a href="{{ route('appointments.show', $selectedCita->id) }}">{{ $selectedCita->codigo }}</a></dd>
            </div>
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Fecha y horario</dt>
                <dd style="font-size: 1.05rem; font-weight: 600; margin: 4px 0 0;">{{ \App\Modules\Shared\Clinic::date($selectedCita->inicio)->format('d/m/Y H:i') }} – {{ \App\Modules\Shared\Clinic::date($selectedCita->fin)->format('H:i') }}</dd>
            </div>
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Médico asignado</dt>
                <dd style="font-size: 1.05rem; font-weight: 600; margin: 4px 0 0;">{{ $selectedCita->medico_nombre }}</dd>
            </div>
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Especialidad</dt>
                <dd style="font-size: 1.05rem; font-weight: 600; margin: 4px 0 0;">{{ $selectedCita->especialidad }}</dd>
            </div>
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Contacto del paciente</dt>
                <dd style="margin: 4px 0 0;">
                    Tel: <strong>{{ $selectedCita->paciente_telefono ?: 'No registrado' }}</strong><br>
                    Correo: <strong>{{ $selectedCita->paciente_correo ?: 'No registrado' }}</strong>
                </dd>
            </div>
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Contacto del médico</dt>
                <dd style="margin: 4px 0 0;">
                    Tel: <strong>{{ $selectedCita->medico_telefono ?: 'No registrado' }}</strong><br>
                    Correo: <strong>{{ $selectedCita->medico_correo ?: 'No registrado' }}</strong>
                </dd>
            </div>
        </dl>
    </section>

    {{-- Formulario para registrar y programar el recordatorio --}}
    <form method="POST" action="{{ route('reminders.store') }}" class="form-panel" style="padding: 24px; border: 1px solid var(--border); border-radius: 12px; background: #fff;">
        @csrf
        <input type="hidden" name="cita_id" value="{{ $selectedCita->id }}">

        <h2>4. Configuración del recordatorio</h2>
        <p class="text-muted">Establece el destinatario, el medio y el momento en que se enviará la notificación.</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin: 18px 0;">
            <div>
                <label for="destinatario" style="font-weight: 600; display: block; margin-bottom: 6px;">Destinatario del recordatorio:</label>
                <select id="destinatario" name="destinatario" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
                    <option value="Paciente" {{ old('destinatario') === 'Paciente' ? 'selected' : '' }}>Paciente ({{ $selectedCita->paciente_nombre }})</option>
                    <option value="Medico" {{ old('destinatario') === 'Medico' ? 'selected' : '' }}>Médico ({{ $selectedCita->medico_nombre }})</option>
                    <option value="Ambos" {{ old('destinatario') === 'Ambos' ? 'selected' : '' }}>Ambos (Paciente y Médico)</option>
                </select>
            </div>

            <div>
                <label for="medio" style="font-weight: 600; display: block; margin-bottom: 6px;">Medio de envío:</label>
                <select id="medio" name="medio" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
                    <option value="Correo" {{ old('medio', 'Correo') === 'Correo' ? 'selected' : '' }}>Correo electrónico (Email)</option>
                    <option value="SMS" {{ old('medio') === 'SMS' ? 'selected' : '' }}>Mensaje de texto SMS</option>
                    <option value="WhatsApp" {{ old('medio') === 'WhatsApp' ? 'selected' : '' }}>WhatsApp</option>
                    <option value="Sistema" {{ old('medio') === 'Sistema' ? 'selected' : '' }}>Notificación interna en Portal</option>
                </select>
                <small class="table-note" style="display:block; margin-top: 4px;">El sistema validará que el destinatario cuente con el medio de contacto registrado.</small>
            </div>

            <div>
                <label for="momento_tipo" style="font-weight: 600; display: block; margin-bottom: 6px;">Momento de envío:</label>
                <select id="momento_tipo" name="momento_tipo" onchange="toggleFechaPersonalizada(this.value)" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
                    <option value="inmediato" {{ old('momento_tipo', 'inmediato') === 'inmediato' ? 'selected' : '' }}>Enviar de inmediato</option>
                    <option value="24h_antes" {{ old('momento_tipo') === '24h_antes' ? 'selected' : '' }}>24 horas antes de la cita</option>
                    <option value="2h_antes" {{ old('momento_tipo') === '2h_antes' ? 'selected' : '' }}>2 horas antes de la cita</option>
                    <option value="1d_8am" {{ old('momento_tipo') === '1d_8am' ? 'selected' : '' }}>1 día antes a las 08:00 AM</option>
                    <option value="personalizado" {{ old('momento_tipo') === 'personalizado' ? 'selected' : '' }}>Fecha y hora personalizada</option>
                </select>
            </div>

            <div id="wrapper-fecha-personalizada" style="{{ old('momento_tipo') === 'personalizado' ? '' : 'display: none;' }}">
                <label for="fecha_personalizada" style="font-weight: 600; display: block; margin-bottom: 6px;">Fecha y hora de envío personalizada:</label>
                <input type="datetime-local" id="fecha_personalizada" name="fecha_personalizada" value="{{ old('fecha_personalizada') }}" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
            </div>
        </div>

        <div style="margin: 18px 0;">
            <label for="mensaje" style="font-weight: 600; display: block; margin-bottom: 6px;">Mensaje del recordatorio:</label>
            <textarea id="mensaje" name="mensaje" rows="4" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 7px; font-family: inherit;">{{ old('mensaje', $defaultMessage) }}</textarea>
            <small class="text-muted">Puedes personalizar este texto antes de guardar.</small>
        </div>

        {{-- Control para pruebas del Flujo Secundario: Error de envío --}}
        <div style="background: #fff9e6; border: 1px solid #f2e2b0; border-radius: 8px; padding: 12px 16px; margin: 18px 0;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: .9rem; cursor: pointer;">
                <input type="checkbox" name="simular_fallo" value="1" {{ old('simular_fallo') ? 'checked' : '' }}>
                <span><strong>Simular fallo de envío</strong> (permite verificar el flujo secundario de error de entrega y registro del fallo).</span>
            </label>
        </div>

        <div class="heading-actions" style="margin-top: 24px;">
            <button type="submit" class="button button-primary">
                <x-icon name="check" /> Registrar y programar recordatorio
            </button>
            <a class="button" href="{{ route('reminders.index') }}">Cancelar</a>
        </div>
    </form>
@endif

<script>
function toggleFechaPersonalizada(value) {
    var el = document.getElementById('wrapper-fecha-personalizada');
    if (el) {
        el.style.display = (value === 'personalizado') ? 'block' : 'none';
    }
}
</script>
@endsection

