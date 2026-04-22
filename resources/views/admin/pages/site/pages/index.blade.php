@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="site.pages.index">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Site Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">İçerik Üretimi</h1>
                    <div class="text-sm text-muted-foreground">
                        Sitede kullanacağın sayfaları, öne çıkan görselleri, ikonları ve yayın akışını tek panelden yönet.
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.site.pages.create') }}" class="kt-btn kt-btn-primary">
                    Yeni İçerik Sayfası
                </a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam Sayfa</div>
                <div class="mt-2 text-3xl font-semibold">{{ $stats['all'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Aktif</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Öne Çıkan</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['featured'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Yayında</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['published'] ?? 0 }}</div>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Sayfa Havuzu</h3>
                    <div class="text-sm text-muted-foreground">
                        İçerik kartlarını hızlıca tara, düzenle ve yayına al.
                    </div>
                </div>

                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        class="kt-input w-full md:w-[260px]"
                        placeholder="Başlık, slug veya içerik ara"
                    >

                    <select name="status" class="kt-select w-full md:w-[200px]" data-kt-select="true">
                        <option value="all" @selected($status === 'all')>Tüm durumlar</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Pasif</option>
                    </select>

                    <button type="submit" class="kt-btn kt-btn-light">Filtrele</button>
                </form>
            </div>

            <div class="kt-card-content p-6 grid gap-4">
                @forelse($pages as $page)
                    @php
                        $featuredUrl = $page->featuredUrl();
                        $isPublished = $page->isPublished();
                    @endphp

                    <div class="rounded-[28px] app-surface-card p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="flex items-start gap-4">
                                <div class="size-18 shrink-0 overflow-hidden rounded-3xl border border-border bg-muted/20">
                                    @if($featuredUrl)
                                        <img src="{{ $featuredUrl }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <div class="grid h-full w-full place-items-center text-muted-foreground">
                                            <i class="ki-outline ki-picture text-2xl"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="grid gap-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-semibold text-foreground">{{ $page->title }}</h3>
                                        <span class="kt-badge kt-badge-sm kt-badge-light">/{{ $page->slug }}</span>
                                        @if($page->is_featured)
                                            <span class="kt-badge kt-badge-sm kt-badge-light-primary">Öne Çıkan</span>
                                        @endif
                                        <span class="kt-badge kt-badge-sm {{ $page->is_active ? 'kt-badge-light-success' : 'kt-badge-light' }}">
                                            {{ $page->is_active ? 'Aktif' : 'Pasif' }}
                                        </span>
                                        <span class="kt-badge kt-badge-sm {{ $isPublished ? 'kt-badge-light-success' : 'kt-badge-light-warning' }}">
                                            {{ $isPublished ? 'Yayında' : 'Planlı / Taslak' }}
                                        </span>
                                    </div>

                                    <div class="text-sm text-muted-foreground">
                                        {{ $page->excerptPreview(180) ?: 'Henüz özet girilmemiş.' }}
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                        <span>{{ $page->readingTimeMinutes() }} dk okuma</span>
                                        <span>•</span>
                                        <span>{{ $page->faqs_count }} SSS</span>
                                        <span>•</span>
                                        <span>{{ $page->counters_count }} sayaç</span>
                                        <span>•</span>
                                        <span>SEO %{{ $page->seoCompletenessScore() }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route('admin.site.pages.toggleActive', $page) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="kt-btn kt-btn-light">
                                        {{ $page->is_active ? 'Pasifleştir' : 'Aktifleştir' }}
                                    </button>
                                </form>

                                <a href="{{ $page->publicUrl() }}" target="_blank" class="kt-btn kt-btn-light">
                                    Ön Yüzde Gör
                                </a>

                                <a href="{{ route('admin.site.pages.edit', $page) }}" class="kt-btn kt-btn-primary">
                                    Düzenle
                                </a>

                                <form method="POST" action="{{ route('admin.site.pages.destroy', $page) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="kt-btn kt-btn-danger" onclick="return confirm('Bu sayfa silinsin mi?')">
                                        Sil
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-border px-6 py-12 text-center">
                        <div class="text-lg font-semibold">Henüz içerik sayfası yok.</div>
                        <div class="mt-2 text-sm text-muted-foreground">
                            İlk sayfanı oluşturup menü, sayaç ve SSS akışlarına bağlayabilirsin.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
