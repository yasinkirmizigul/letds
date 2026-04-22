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
@endphp

@section('content')
    <div class="kt-container-fixed max-w-[90%]">
        <div class="grid gap-5 lg:gap-7.5">

            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-semibold">Audit #{{ $row->id }}</h1>
                    <div class="text-sm text-muted-foreground">{{ $row->created_at }}</div>
                </div>
                <a class="kt-btn kt-btn-light" href="{{ route('admin.audit-logs.index') }}">Geri</a>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Method</div>
                        <div class="mt-2"><span class="{{ $methodBadge($row->method) }}">{{ strtoupper((string) $row->method) }}</span></div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Status</div>
                        <div class="mt-2"><span class="{{ $statusBadge($row->status) }}">{{ $row->status }}</span></div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Action</div>
                        <div class="mt-2 font-semibold">{{ $row->action ?: '-' }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Sure</div>
                        <div class="mt-2 font-semibold">{{ (int) ($row->duration_ms ?? 0) }} ms</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <div class="kt-card">
                    <div class="kt-card-content p-5 grid gap-3">
                        <div class="text-sm font-semibold">Istek Ozeti</div>
                        <div class="text-sm">Route: <span class="font-medium">{{ $row->route ?: '-' }}</span></div>
                        <div class="text-sm">URI: <span class="font-medium break-all">{{ $row->uri ?: '-' }}</span></div>
                        <div class="text-sm">IP: <span class="font-medium">{{ $row->ip ?: '-' }}</span></div>
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-content p-5 grid gap-3">
                        <div class="text-sm font-semibold">Kullanici</div>
                        <div class="text-sm">Ad: <span class="font-medium">{{ $row->user_name ?: '-' }}</span></div>
                        <div class="text-sm">E-posta: <span class="font-medium">{{ $row->user_email ?: '-' }}</span></div>
                        <div class="text-sm">User agent:</div>
                        <div class="text-xs text-muted-foreground break-words">{{ $row->user_agent ?: '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm font-semibold mb-3">Query</div>
                        <pre class="text-xs whitespace-pre-wrap overflow-auto">{{ json_encode($row->query ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm font-semibold mb-3">Payload</div>
                        <pre class="text-xs whitespace-pre-wrap overflow-auto">{{ json_encode($row->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm font-semibold mb-3">Context</div>
                        <pre class="text-xs whitespace-pre-wrap overflow-auto">{{ json_encode($row->context ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
