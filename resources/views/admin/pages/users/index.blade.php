@extends('admin.layouts.dash.base')

@section('content')
    <div class="px-4 lg:px-6">
        @include('admin.partials._flash')

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Kullanıcılar</h1>
            <a class="kt-btn kt-btn-primary" href="{{ route('admin.users.create') }}">Yeni Kullanıcı</a>
        </div>

        <form class="flex gap-2 mb-4" method="GET">
            <input class="kt-input" name="q" value="{{ $q }}" placeholder="Ad / Email ara..."/>
            <button class="kt-btn kt-btn-light" type="submit">Ara</button>
        </form>

        <div class="kt-card">
            <div class="kt-card-content p-0">
                <table class="kt-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad</th>
                        <th>Email</th>
                        <th>Roller</th>
                        <th>Aktif</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>{{ $u->id }}</td>
                            <td>{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($u->roles as $r)
                                        <span class="kt-badge kt-badge-sm">{{ $r->slug }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>{{ $u->is_active ? 'Evet' : 'Hayır' }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <a class="kt-btn kt-btn-sm kt-btn-light" href="{{ route('admin.users.edit', $u) }}">Düzenle</a>
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                          onsubmit="return confirm('Silinsin mi?')">
                                        @csrf @method('DELETE')
                                        <button class="kt-btn kt-btn-sm kt-btn-danger" type="submit">Sil</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
