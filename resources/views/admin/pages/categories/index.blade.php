@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6">

        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">{{ $pageTitle }}</h1>
                <div class="text-sm text-muted-foreground">Ortak kategori sistemi (blog/galeri/ürün)</div>
            </div>

            @if(auth()->user()->hasPermission('category.create'))
                <a href="{{ route('admin.categories.create') }}" class="kt-btn kt-btn-primary">Yeni Kategori</a>
            @endif
        </div>

        <div class="kt-card">
            <div class="kt-card-content p-6">

                <table id="categories_table" class="w-full">
                    <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Slug</th>
                        <th>Üst Kategori</th>
                        <th>Blog</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($categories as $cat)
                        <tr>
                            <td class="font-medium">{{ $cat->name }}</td>
                            <td class="text-muted-foreground">{{ $cat->slug }}</td>
                            <td class="text-muted-foreground">{{ $cat->parent?->name ?? '-' }}</td>
                            <td>{{ $cat->blog_posts_count }}</td>
                            <td class="text-right">
                                <div class="inline-flex gap-2">
                                    @if(auth()->user()->hasPermission('category.update'))
                                        <a class="kt-btn kt-btn-light kt-btn-sm"
                                           href="{{ route('admin.categories.edit', ['category' => $cat->id]) }}">
                                            Düzenle
                                        </a>
                                    @endif

                                    @if(auth()->user()->hasPermission('category.delete'))
                                        <form method="POST"
                                              action="{{ route('admin.categories.destroy', ['category' => $cat->id]) }}"
                                              onsubmit="return confirm('Kategori silinsin mi? (İlişkiler otomatik kaldırılır)')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="kt-btn kt-btn-danger kt-btn-sm">Sil</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
        </div>

    </div>
@endsection

@push('page_js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.DataTable) return;

            const tableEl = document.getElementById('categories_table');
            if (!tableEl) return;

            new DataTable(tableEl, {
                pageLength: 25,
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, searchable: false, targets: [4] },
                ],
                language: {
                    search: "Ara:",
                    lengthMenu: "_MENU_ kayıt göster",
                    info: "_TOTAL_ kayıttan _START_ - _END_ arası",
                    infoEmpty: "Kayıt yok",
                    zeroRecords: "Sonuç bulunamadı",
                    paginate: { first: "İlk", last: "Son", next: ">", previous: "<" },
                }
            });
        });
    </script>
@endpush
