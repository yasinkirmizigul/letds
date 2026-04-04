@extends('site.layouts.main.app')

@section('content')
    <div class="container py-5" style="max-width: 420px;">
        <h3 class="mb-4">Üye Girişi</h3>

        <form method="POST" action="{{ route('member.login.post') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">E-posta</label>
                <input type="email" name="email" class="form-control" required>
                @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
        </form>
    </div>
@endsection
