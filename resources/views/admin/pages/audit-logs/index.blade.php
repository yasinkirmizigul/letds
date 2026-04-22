@extends('admin.layouts.main.app')

@php
    $method = fn ($value) => strtoupper((string) $value);

    $methodBadge = function ($value) use ($method) {
        return match ($method($value)) {
            'POST' => 'kt-badge kt-badge-sm kt-badge-primary',
            'PUT', 'PATCH' => 'kt-badge kt-badge-sm kt-badge-warning',
            'DELETE' => 'kt-badge kt-badge-sm kt-badge-danger',
            'CLI' => 'kt-badge kt-badge-sm kt-badge-light-info',
            default => 'kt-badge kt-badge-sm kt-badge-light',
        };
    };

    $statusBadge = function ($value) {
        $code = (int) $value;
        if ($code >= 200 && $code < 300) return 'kt-badge kt-badge-sm kt-badge-success';
        if ($code >= 300 && $code < 400) return 'kt-badge kt-badge-sm kt-badge-info';
        if ($code >= 400 && $code < 500) return 'kt-badge kt-badge-sm kt-badge-warning';
        if ($code >= 500) return 'kt-badge kt-badge-sm kt-badge-danger';
        return 'kt-badge kt-badge-sm kt-badge-light';
    };

    $mode = $mode ?? 'all';
@endphp

@section('content')
    <div class="kt-container-fixed max-w-[90%]" data-page="audit.index">
        <div class="grid gap-5 lg:gap-7.5">

            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-semibold">Audit Log</h1>
                    <div class="text-sm text-muted-foreground">Admin akislarini, hatalari ve sistem kayitlarini tek ekrandan incele.</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Toplam kayit</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($stats['total'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Kullanici istegi</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($stats['user'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Sistem / CLI</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($stats['system'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">4xx / 5xx</div>
                        <div class="mt-2 text-2xl font-semibold text-danger">{{ number_format((int) ($stats['errors'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Yavas istek</div>
                        <div class="mt-2 text-2xl font-semibold text-warning">{{ number_format((int) ($stats['slow'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-content p-5 grid gap-4">

                    <form class="grid gap-3 xl:grid-cols-[minmax(0,1.6fr)_auto_minmax(11rem,0.9fr)_minmax(11rem,0.9fr)_minmax(8rem,0.6fr)_minmax(7rem,0.5fr)_auto_auto]">
                        <input
                            class="kt-input w-full"
                            name="q"
                            value="{{ $filters['q'] ?? '' }}"
                            placeholder="Route, uri, kullanici veya IP ara..." />

                        <div class="flex items-center gap-2 flex-wrap">
                            <a class="kt-btn kt-btn-sm {{ $mode === 'all' ? 'kt-btn-primary' : 'kt-btn-light' }}"
                               href="{{ request()->fullUrlWithQuery(['mode' => 'all', 'page' => 1]) }}">
                                Tumu
                            </a>
                            <a class="kt-btn kt-btn-sm {{ $mode === 'user' ? 'kt-btn-primary' : 'kt-btn-light' }}"
                               href="{{ request()->fullUrlWithQuery(['mode' => 'user', 'page' => 1]) }}">
                                Kullanici
                            </a>
                            <a class="kt-btn kt-btn-sm {{ $mode === 'system' ? 'kt-btn-primary' : 'kt-btn-light' }}"
                               href="{{ request()->fullUrlWithQuery(['mode' => 'system', 'page' => 1]) }}">
                                System / CLI
                            </a>
                        </div>

                        <select class="kt-select w-full" name="method" data-kt-select="true" data-kt-select-placeholder="Method">
                            <option value="">Method</option>
                            @foreach(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'CLI'] as $item)
                                <option value="{{ $item }}" @selected(($filters['method'] ?? '') === $item)>{{ $item }}</option>
                            @endforeach
                        </select>

                        <select class="kt-select w-full" name="action" data-kt-select="true" data-kt-select-placeholder="Action">
                            <option value="">Action</option>
                            @foreach($actionOptions as $item)
                                <option value="{{ $item }}" @selected(($filters['action'] ?? '') === $item)>{{ $item }}</option>
                            @endforeach
                        </select>

                        <input class="kt-input w-full" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="Status" />

                        <select class="kt-select w-full" name="perpage" data-kt-select="true">
                            @foreach([25, 50, 100, 200] as $size)
                                <option value="{{ $size }}" @selected(($filters['perpage'] ?? 25) == $size)>{{ $size }}</option>
                            @endforeach
                        </select>

                        <button class="kt-btn kt-btn-primary" type="submit">Filtrele</button>
                        <a class="kt-btn kt-btn-light" href="{{ route('admin.audit-logs.index') }}">Sifirla</a>
                    </form>

                    @if(request()->query())
                        <div class="flex flex-wrap gap-2">
                            @foreach(request()->query() as $key => $value)
                                @continue($key === 'page' || $value === null || $value === '')
                                <a class="kt-badge kt-badge-sm kt-badge-light"
                                   href="{{ route('admin.audit-logs.index', collect(request()->query())->except($key)->all()) }}">
                                    {{ $key }}: {{ is_array($value) ? '...' : $value }} x
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="kt-scrollable-x-auto overflow-y-hidden">
                        <table class="kt-table table-auto kt-table-border w-full">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Zaman</th>
                                <th>Kullanici</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Action</th>
                                <th>Route</th>
                                <th>URI</th>
                                <th>IP</th>
                                <th>Sure</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>
                                        <a class="kt-link" href="{{ route('admin.audit-logs.show', $row) }}">{{ $row->id }}</a>
                                    </td>
                                    <td class="whitespace-nowrap">{{ $row->created_at }}</td>
                                    <td class="min-w-[220px]">
                                        <div class="font-medium">
                                            <a class="kt-link" href="{{ route('admin.audit-logs.index', array_merge(request()->query(), ['q' => $row->user_name, 'page' => 1])) }}">
                                                {{ $row->user_name ?: '-' }}
                                            </a>
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            <a class="kt-link" href="{{ route('admin.audit-logs.index', array_merge(request()->query(), ['q' => $row->user_email, 'page' => 1])) }}">
                                                {{ $row->user_email ?: '' }}
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <a class="kt-link" href="{{ route('admin.audit-logs.index', array_merge(request()->query(), ['method' => strtoupper((string) $row->method), 'page' => 1])) }}">
                                            <span class="{{ $methodBadge($row->method) }}">{{ strtoupper((string) $row->method) }}</span>
                                        </a>
                                    </td>
                                    <td>
                                        <a class="kt-link" href="{{ route('admin.audit-logs.index', array_merge(request()->query(), ['status' => $row->status, 'page' => 1])) }}">
                                            <span class="{{ $statusBadge($row->status) }}">{{ $row->status }}</span>
                                        </a>
                                    </td>
                                    <td class="min-w-[180px]">
                                        @if($row->action)
                                            <a class="kt-link" href="{{ route('admin.audit-logs.index', array_merge(request()->query(), ['action' => $row->action, 'page' => 1])) }}">
                                                {{ $row->action }}
                                            </a>
                                        @else
                                            <span class="text-muted-foreground">-</span>
                                        @endif
                                    </td>
                                    <td class="min-w-[220px]">{{ $row->route ?: '-' }}</td>
                                    <td class="min-w-[260px]">{{ $row->uri ?: '-' }}</td>
                                    <td>
                                        <a class="kt-link" href="{{ route('admin.audit-logs.index', array_merge(request()->query(), ['q' => $row->ip, 'page' => 1])) }}">
                                            {{ $row->ip ?: '-' }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="{{ ($row->duration_ms ?? 0) >= 1000 ? 'text-warning font-medium' : 'text-muted-foreground' }}">
                                            {{ (int) ($row->duration_ms ?? 0) }} ms
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted-foreground p-6">Kayit yok.</td>
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
