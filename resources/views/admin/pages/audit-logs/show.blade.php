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
    <div class="kt-container-fixed max-w-[90%]">
        <div class="grid gap-5 lg:gap-7.5">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Audit #{{ $row->id }}</h1>
                    <div class="text-sm text-muted-foreground">{{ $row->created_at }}</div>
                </div>
                <a class="kt-btn kt-btn-light" href="{{ route('admin.audit-logs.index') }}">Geri</a>
            </div>

            <div class="kt-card">
                <div class="kt-card-content p-6 grid gap-4">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="kt-card p-4">
                            <div class="text-sm font-semibold mb-2">İstek</div>
                            <div class="text-sm">Action: <span class="font-medium">{{ $row->action }}</span></div>
                            <div class="text-sm">Route: <span class="font-medium">{{ $row->route }}</span></div>
                            <div class="text-sm">
                                Method:
                                <span class="{{ $methodBadge($row->method) }}">{{ strtoupper($row->method) }}</span>
                            </div>

                            <div class="text-sm">
                                Status:
                                <span class="{{ $statusBadge($row->status) }}">{{ $row->status }}</span>
                            </div>

                            <div class="text-sm">Süre: <span class="font-medium">{{ $row->duration_ms }} ms</span></div>
                            <div class="text-sm">URI: <span class="font-medium">{{ $row->uri }}</span></div>
                        </div>

                        <div class="kt-card p-4">
                            <div class="text-sm font-semibold mb-2">Kullanıcı</div>
                            <div class="text-sm">User: <span class="font-medium">{{ $row->user_name ?? '-' }}</span></div>
                            <div class="text-sm">Email: <span class="font-medium">{{ $row->user_email ?? '-' }}</span></div>
                            <div class="text-sm">IP: <span class="font-medium">{{ $row->ip }}</span></div>
                            <div class="text-sm">UA:</div>
                            <div class="text-xs text-muted-foreground break-words">{{ $row->user_agent }}</div>
                        </div>
                    </div>

                    <div class="kt-card p-4">
                        <div class="text-sm font-semibold mb-2">Query</div>
                        <pre class="text-xs whitespace-pre-wrap">{{ json_encode($row->query ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>

                    <div class="kt-card p-4">
                        <div class="text-sm font-semibold mb-2">Payload</div>
                        <pre class="text-xs whitespace-pre-wrap">{{ json_encode($row->payload ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>

                    <div class="kt-card p-4">
                        <div class="text-sm font-semibold mb-2">Context</div>
                        <pre class="text-xs whitespace-pre-wrap">{{ json_encode($row->context ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection
