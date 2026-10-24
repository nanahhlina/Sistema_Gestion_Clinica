@props(['name' => 'grid'])
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('cross')<path d="M9 2h6v7h7v6h-7v7H9v-7H2V9h7z" />@break
        @case('patients') @case('pacientes') @case('users')<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /><circle cx="9" cy="7" r="4" />@break
        @case('calendar') @case('citas') @case('agenda')<rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 11h18M8 15h1m6 0h1m-8 3h1m6 0h1" />@break
        @case('stethoscope') @case('consultas') @case('clinica')<path d="M6 3H4v6a5 5 0 0 0 10 0V3h-2M9 14v2a5 5 0 0 0 10 0v-2" /><circle cx="19" cy="11" r="3" />@break
        @case('file') @case('history') @case('historia') @case('file-text')<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 12h8m-8 4h8m-8-8h2" />@break
        @case('doctor') @case('doctors') @case('medicos')<circle cx="12" cy="6" r="4" /><path d="M3 22v-3a9 9 0 0 1 18 0v3zM8 11v5m8-5v5" /><circle cx="8" cy="17" r="1.5" /><circle cx="16" cy="17" r="1.5" />@break
        @case('user') @case('usuarios') @case('portal')<circle cx="12" cy="7" r="4" /><path d="M3 22v-3a6 6 0 0 1 6-6h6a6 6 0 0 1 6 6v3" />@break
        @case('flask') @case('lab') @case('laboratorio') @case('flask-conical')<path d="M9 3h6m-5 0v6l-6 10a2 2 0 0 0 2 3h12a2 2 0 0 0 2-3L14 9V3M8 14h8" />@break
        @case('chart') @case('reportes') @case('bar-chart') @case('chart-column')<path d="M3 3v18h18M7 16v-4m5 4V7m5 9v-6" />@break
        @case('shield') @case('roles') @case('auditoria') @case('shield-check')<path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6zM8 12l3 3 5-6" />@break
        @case('clock') @case('horarios')<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />@break
        @case('logout')<path d="M9 21H4V3h5m6 4 5 5-5 5m-7-5h12" />@break
        @case('arrow-left')<path d="m10 5-7 7 7 7M3 12h18" />@break
        @case('arrow-right')<path d="m14 5 7 7-7 7M3 12h18" />@break
        @case('plus')<path d="M12 5v14M5 12h14" />@break
        @case('search')<circle cx="10.5" cy="10.5" r="6.5" /><path d="m16 16 5 5" />@break
        @case('check')<path d="m5 12 4 4L19 6" />@break
        @case('alert')<path d="m12 3 10 18H2zM12 9v5m0 3h.01" />@break
        @case('eye')<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7" /><circle cx="12" cy="12" r="3" />@break
        @case('edit')<path d="m16 3 5 5L8 21H3v-5zM13 6l5 5" />@break
        @case('download')<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5" />@break
        @case('lock')<rect x="4" y="10" width="16" height="12" rx="2" /><path d="M8 10V6a4 4 0 0 1 8 0v4m-4 5v3" />@break
        @default<rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" />
    @endswitch
</svg>
