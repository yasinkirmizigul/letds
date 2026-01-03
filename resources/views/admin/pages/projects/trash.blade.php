@extends('admin.layouts.main.app')

@section('content')
    @php request()->merge(['mode' => 'trash']); @endphp
    @include('admin.pages.projects.index', ['mode' => 'trash'])
@endsection
