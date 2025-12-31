@extends('admin.layouts.main.app')
@php
    $method = fn($m) => strtoupper((string) $m);

    $methodBadge = function ($m) use ($method) {
        $m = $method($m);
        return match ($m) {
            'POST' => 'kt-badge kt-badge-sm kt-badge-primary',
            'PUT', 'PATCH' => 'kt-badge kt-badge-sm kt-badge-warning',
            'DELETE' => 'kt-badge kt-badge-sm kt-badge-danger',
            default => 'kt-badge kt-badge-sm kt-badge-light',
        };
    };

    $statusBadge = function ($s) {
        $s = (int) $s;
        if ($s >= 200 && $s < 300) return 'kt-badge kt-badge-sm kt-badge-success';
        if ($s >= 300 && $s < 400) return 'kt-badge kt-badge-sm kt-badge-info';
        if ($s >= 400 && $s < 500) return 'kt-badge kt-badge-sm kt-badge-warning';
        if ($s >= 500) return 'kt-badge kt-badge-sm kt-badge-danger';
        return 'kt-badge kt-badge-sm kt-badge-light';
    };
@endphp
@section('content')
    <div class="kt-container-fixed max-w-[90%]" data-page="audit.index">
        <div class="grid gap-5 lg:gap-7.5">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Audit Log</h1>
                    <div class="text-sm text-muted-foreground">Admin işlemleri kayıt altına alınır.</div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-content p-5 grid gap-4">

                    <form class="flex flex-col md:flex-row gap-3 items-center">
                        <input class="kt-input w-full" name="q" value="{{ $filters['q'] ?? '' }}"
                               placeholder="Ara: route, uri, user, ip..." />
                        @php($mode = request('mode', 'all'))

                        <div class="flex items-center gap-2">
                            <a class="kt-btn kt-btn-sm {{ $mode==='all'?'kt-btn-primary':'kt-btn-light' }}"
                               href="{{ request()->fullUrlWithQuery(['mode'=>'all']) }}">Tümü</a>

                            <a class="kt-btn kt-btn-sm {{ $mode==='user'?'kt-btn-primary':'kt-btn-light' }}"
                               href="{{ request()->fullUrlWithQuery(['mode'=>'user']) }}">Kullanıcı</a>

                            <a class="kt-btn kt-btn-sm {{ $mode==='system'?'kt-btn-primary':'kt-btn-light' }}"
                               href="{{ request()->fullUrlWithQuery(['mode'=>'system']) }}">SYSTEM/CLI</a>
                        </div>

                        <select class="kt-select w-full md:w-44" name="method"  data-kt-select="true"
                                data-kt-select-placeholder="Metod"
                                data-kt-select-config='{
                                    "optionsClass": "kt-scrollable overflow-auto max-h-[250px]"
                                }'>
                            <option value="">Method</option>
                            @foreach(['GET','POST','PUT','PATCH','DELETE'] as $m)
                                <option value="{{ $m }}" @selected(($filters['method'] ?? '') === $m)>{{ $m }}</option>
                            @endforeach
                        </select>

                        <select class="kt-select w-full md:w-44" name="action"  data-kt-select="true"
                                data-kt-select-placeholder="Action"
                                data-kt-select-config='{
                                    "optionsClass": "kt-scrollable overflow-auto max-h-[250px]"
                                }'>
                            <option value="">Action</option>
                            <option value="request" @selected(($filters['action'] ?? '') === 'request')>request</option>
                        </select>

                        <input class="kt-input w-full md:w-32" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="Status" />

                        <select class="kt-select w-full md:w-32" name="perpage"  data-kt-select="true"
                                data-kt-select-config='{
			"optionsClass": "kt-scrollable overflow-auto max-h-[250px]"
		}'>
                            @foreach([25,50,100,200] as $pp)
                                <option value="{{ $pp }}" @selected(($filters['perpage'] ?? 25) == $pp)>{{ $pp }}</option>
                            @endforeach
                        </select>
                        <button class="kt-btn kt-btn-primary" type="submit">Filtrele</button>
                        <a class="kt-btn kt-btn-light" href="{{ route('admin.audit-logs.index') }}">Sıfırla</a>
                    </form>
                    @if(request()->query())
                        <div class="flex flex-wrap gap-2">
                            @foreach(request()->query() as $k => $v)
                                @continue($k === 'page')
                                <a class="kt-badge kt-badge-sm kt-badge-light"
                                   href="{{ route('admin.audit-logs.index', collect(request()->query())->except($k)->all()) }}">
                                    {{ $k }}: {{ is_array($v) ? '...' : $v }} ✕
                                </a>
                            @endforeach
                        </div>
                    @endif
                    <div class="kt-scrollable-x-auto overflow-y-hidden">
                        <table class="kt-table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Zaman</th>
                                <th>Kullanıcı</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Route</th>
                                <th>URI</th>
                                <th>IP</th>
                                <th>Süre</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($rows as $r)
                                <tr>
                                    <td>
                                        <a class="kt-link" href="{{ route('admin.audit-logs.show', $r) }}">{{ $r->id }}</a>
                                    </td>
                                    <td class="whitespace-nowrap">{{ $r->created_at }}</td>
                                    <td class="min-w-[180px]">
                                        <div class="font-medium">
                                            <a class="kt-link" href="{{ route('admin.audit-logs.index', array_merge(request()->query(), ['q' => $r->user_name, 'page' => 1])) }}">
                                                {{ $r->user_name ?? '-' }}
                                            </a>
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            <a class="kt-link" href="{{ route('admin.audit-logs.index', array_merge(request()->query(), ['q' => $r->user_email, 'page' => 1])) }}">
                                                {{ $r->user_email ?? '' }}
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <a class="kt-link" href="{{ route('admin.audit-logs.index', array_merge(request()->query(), ['method' => $r->method, 'page' => 1])) }}">
                                            <span class="{{ $methodBadge($r->method) }}">{{ strtoupper($r->method) }}</span>
                                        </a>
                                    </td>

                                    <td>
                                        <a class="kt-link" href="{{ route('admin.audit-logs.index', array_merge(request()->query(), ['status' => $r->status, 'page' => 1])) }}">
                                            <span class="{{ $statusBadge($r->status) }}">{{ $r->status }}</span>
                                        </a>
                                    </td>

                                    <td class="min-w-[220px]">{{ $r->route }}</td>
                                    <td class="min-w-[240px]">{{ $r->uri }}</td>

                                    <td>
                                        <a class="kt-link" href="{{ route('admin.audit-logs.index', array_merge(request()->query(), ['q' => $r->ip, 'page' => 1])) }}">
                                            {{ $r->ip }}
                                        </a>
                                    </td>
                                    <td>{{ $r->duration_ms }} ms</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted-foreground p-6">Kayıt yok.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-center">
                        {{ $rows->links() }}
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection
