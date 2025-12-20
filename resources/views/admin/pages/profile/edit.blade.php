@extends('admin.layouts.main.app')

@section('content')
    @php($u = auth()->user())

    <div class="kt-container-fixed" data-page="profile.edit">
        <div class="grid gap-5 lg:gap-7.5">
            @includeIf('admin.partials._flash')

            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <h3 class="kt-card-title">Profilim</h3>
                </div>

                <div class="kt-card-content p-6 grid gap-6">

                    <div class="flex items-center gap-4">
                        <img class="size-14 rounded-full border"
                             src="{{ $u->avatarUrl() }}"
                             alt="{{ $u->name ?? 'User' }}">

                        <div class="grid gap-2">
                            <div class="text-sm font-semibold">{{ $u->name ?? 'Kullanıcı' }}</div>
                            <div class="text-xs text-muted-foreground">{{ $u->email }}</div>

                            <div class="flex items-center gap-2">
                                <button class="kt-btn kt-btn-light"
                                        type="button"
                                        data-media-picker="true"
                                        data-media-picker-target="#avatar_media_id"
                                        data-media-picker-preview="#avatar_preview"
                                        data-media-picker-mime="image/*">
                                    <i class="ki-filled ki-image"></i>
                                    Avatar Seç
                                </button>

                                <form method="POST" action="{{ route('admin.profile.avatar') }}">
                                    @csrf
                                    <input type="hidden" id="avatar_media_id" name="avatar_media_id" value="{{ $u->avatar_media_id }}">
                                    <button class="kt-btn kt-btn-primary">Kaydet</button>
                                </form>

                                @if($u->avatar_media_id)
                                    <form method="POST" action="{{ route('admin.profile.avatar') }}">
                                        @csrf
                                        <input type="hidden" name="avatar_media_id" value="">
                                        <button class="kt-btn kt-btn-outline">Sıfırla</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    <img id="avatar_preview"
                         class="hidden size-20 rounded-full border"
                         alt="preview" />

                </div>
            </div>
        </div>
    </div>

    {{-- Media Picker modal --}}
    @include('admin.partials.media._picker-modal')

@endsection
