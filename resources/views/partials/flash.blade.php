@foreach(['success' => 'success', 'status' => 'info', 'info' => 'info', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session()->has($key) && is_string(session($key)))<div class="alert alert-{{ $tone }}" role="status"><x-icon :name="$tone === 'success' ? 'check' : 'alert'" /><p>{{ session($key) }}</p></div>@endif
@endforeach
@if($errors->any())<div class="alert alert-danger validation-summary" role="alert" tabindex="-1" data-validation-summary><x-icon name="alert" /><div><strong>Revisa la información antes de continuar.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
