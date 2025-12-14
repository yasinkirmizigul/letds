@if(session('ok'))
    <div class="kt-alert kt-alert-success mb-4">
        <div class="kt-alert-text">{{ session('ok') }}</div>
    </div>
@endif

@if($errors->has('error'))
    <div class="kt-alert kt-alert-danger mb-4">
        <div class="kt-alert-text">{{ $errors->first('error') }}</div>
    </div>
@endif
