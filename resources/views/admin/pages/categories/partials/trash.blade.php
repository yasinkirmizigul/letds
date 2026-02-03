@extends('admin.layouts.main.app')

@section('content')
    <div data-page="categories.trash">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Silinen Kategoriler</h3>
            </div>

            <div class="kt-card-body">
                <table id="categories_trash_table"
                       class="kt-table w-full"
                       data-ajax="{{ route('admin.categories.trash.list') }}">
                    <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Slug</th>
                        <th>Üst</th>
                        <th>Silinme Tarihi</th>
                        <th></th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
