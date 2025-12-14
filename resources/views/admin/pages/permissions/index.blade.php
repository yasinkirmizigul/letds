
@extends('admin.layouts.dash.base')

@section('content')
    <div class="px-4 lg:px-6">
        @include('admin.partials._flash')

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Yetkiler</h1>
            <a class="kt-btn kt-btn-primary" href="{{ route('admin.permissions.create') }}">Yeni Yetki</a>
        </div>

        <div class="kt-card">
            <div class="kt-card-content p-0">
                <table class="kt-table">
                    <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Slug</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($permissions as $p)
                        <tr>
                            <td>{{ $p->name }}</td>
                            <td>{{ $p->slug }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <a class="kt-btn kt-btn-sm kt-btn-light" href="{{ route('admin.permissions.edit', $p) }}">Düzenle</a>

                                    <form method="POST" action="{{ route('admin.permissions.destroy', $p) }}"
                                          onsubmit="return confirm('Yetki silinsin mi?')">
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
                    {{ $permissions->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

