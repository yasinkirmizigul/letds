
@extends('admin.layouts.dash.base')

@section('content')
    <div class="px-4 lg:px-6">
        @include('admin.partials._flash')

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Roller</h1>
            <a class="kt-btn kt-btn-primary" href="{{ route('admin.roles.create') }}">Yeni Rol</a>
        </div>

        <div class="kt-card">
            <div class="kt-card-content p-0">
                <table class="kt-table">
                    <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Slug</th>
                        <th>Kullanıcı</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($roles as $r)
                        <tr>
                            <td>{{ $r->name }}</td>
                            <td>{{ $r->slug }}</td>
                            <td>{{ $r->users_count }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <a class="kt-btn kt-btn-sm kt-btn-light" href="{{ route('admin.roles.edit', $r) }}">Düzenle</a>

                                    <form method="POST" action="{{ route('admin.roles.destroy', $r) }}"
                                          onsubmit="return confirm('Rol silinsin mi?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="kt-btn kt-btn-sm kt-btn-danger" type="submit">Sil</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $roles->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
