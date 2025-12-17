@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="kt-card kt-card-grid min-w-full">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex flex-col">
                        <h3 class="kt-card-title">Blog Yazıları</h3>
                        <div class="text-sm text-muted-foreground">Blog yazılarını yönetin</div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">

                        <form method="GET" action="{{ route('admin.blog.index') }}" class="flex items-center gap-2 flex-wrap">

                            <input
                                id="blogSearch"
                                name="q"
                                type="text"
                                class="kt-input kt-input-sm"
                                placeholder="Başlık / kısa bağlantı ara..."
                                value="{{ $q ?? '' }}"
                            />

                            <select
                                id="blogCategoryFilter"
                                name="category_ids[]"
                                multiple
                                class="kt-input kt-input-sm min-w-[260px]"
                            >
                                @foreach(($categoryOptions ?? []) as $opt)
                                    <option value="{{ $opt['id'] }}"
                                        @selected(in_array($opt['id'], $selectedCategoryIds ?? [], true))>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-light">
                                Filtrele
                            </button>

                            @php
                                $hasFilter = !empty($q) || !empty($selectedCategoryIds);
                            @endphp

                            @if($hasFilter)
                                <a href="{{ route('admin.blog.index') }}" class="kt-btn kt-btn-sm kt-btn-ghost">
                                    Temizle
                                </a>
                            @endif
                            <input type="hidden" name="perpage" value="{{ $perPage ?? 25 }}">

                        </form>

                        @if(auth()->user()->hasPermission('blog.create'))
                            <a href="{{ route('admin.blog.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                Yeni Yazı
                            </a>
                        @endif
                    </div>

                </div>

                <div class="kt-card-content">
                    <div class="grid" id="blog_dt">

                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border w-full" id="blog_table">
                                <thead>
                                <tr>
                                    <th class="w-[80px]">ID</th>
                                    <th class="min-w-[360px]">Yazı</th>
                                    <th class="min-w-[280px]">Kısa Bağlantı</th>
                                    <th class="min-w-[280px]">Durum</th>
                                    <th class="min-w-[190px]">Güncelleme Tarihi</th>
                                    <th class="w-[60px]"></th>
                                    <th class="w-[60px]"></th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($posts as $p)
                                    @php
                                        $img = $p->featured_image ? asset('storage/'.$p->featured_image) : null;
                                    @endphp

                                    <tr data-row-id="{{ $p->id }}">
                                        <td class="text-sm text-secondary-foreground">{{ $p->id }}</td>

                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="size-[44px] rounded-full overflow-hidden bg-muted flex items-center justify-center">
                                                    @if($img)
                                                        <a href="javascript:void(0)"
                                                           class="js-img-popover block size-full"
                                                           data-popover-img="{{ $img }}">
                                                            <img src="{{ $img }}" alt="" class="size-full object-cover"/>
                                                        </a>
                                                    @else
                                                        <i class="ki-outline ki-picture text-muted-foreground text-lg"></i>
                                                    @endif
                                                </div>

                                                <div class="flex flex-col gap-0.5">
                                                    <span class="font-semibold">{{ $p->title }}</span>
                                                    <span class="text-sm text-muted-foreground">{{ $p->author?->name ?? '-' }}</span>

                                                    @if($p->categories?->count())
                                                        <div class="flex flex-wrap gap-1 mt-1">
                                                            @foreach($p->categories as $c)
                                                                <span class="kt-badge kt-badge-sm kt-badge-light">
                                                                    {{ $c->name }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td class="text-sm text-secondary-foreground">{{ $p->slug }}</td>

                                        <td>
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="js-badge">
                                                    @if($p->is_published)
                                                        <span class="kt-badge kt-badge-sm kt-badge-success">Yayında</span>
                                                    @else
                                                        <span class="kt-badge kt-badge-sm kt-badge-light">Taslak</span>
                                                    @endif
                                                </div>

                                                @if(auth()->user()->hasPermission('blog.update'))
                                                    <label class="kt-switch kt-switch-sm">
                                                        <input
                                                            class="js-publish-toggle kt-switch kt-switch-mono"
                                                            type="checkbox"
                                                            data-url="{{ route('admin.blog.togglePublish', $p) }}"
                                                            @checked($p->is_published)
                                                        />
                                                    </label>
                                                @endif
                                            </div>

                                            <div class="text-sm text-muted-foreground mt-1 js-published-at">
                                                @if($p->published_at)
                                                    Yayın Tarihi: {{ $p->published_at->format('d.m.Y H:i') }}
                                                @endif
                                            </div>
                                        </td>

                                        <td class="text-sm text-secondary-foreground">
                                            {{ $p->updated_at?->format('d.m.Y H:i') }}
                                        </td>

                                        <td>
                                            @if(auth()->user()->hasPermission('blog.update'))
                                                <a href="{{ route('admin.blog.edit', $p) }}"
                                                   class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                                   title="Düzenle">
                                                    <i class="ki-filled ki-notepad-edit"></i>
                                                </a>
                                            @endif
                                        </td>

                                        <td>
                                            @if(auth()->user()->hasPermission('blog.delete'))
                                                <form method="POST"
                                                      action="{{ route('admin.blog.destroy', $p) }}"
                                                      onsubmit="return confirm('Bu yazıyı silmek istiyor musunuz?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                                            title="Sil">
                                                        <i class="ki-filled ki-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <template id="dt-empty-blog">
                            <tr data-kt-empty-row="true">
                                <td colspan="7" class="py-12">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <i class="ki-outline ki-document text-3xl text-muted-foreground"></i>
                                        <div class="font-semibold">Henüz blog yazısı yok</div>
                                        <div class="text-sm text-muted-foreground">Yeni yazı oluştur.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template id="dt-zero-blog">
                            <tr data-kt-zero-row="true">
                                <td colspan="7" class="py-12">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <i class="ki-outline ki-magnifier text-3xl text-muted-foreground"></i>
                                        <div class="font-semibold">Sonuç bulunamadı</div>
                                        <div class="text-sm text-muted-foreground">Aramanı değiştirip tekrar dene.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        {{-- FOOTER --}}
                        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                            <div class="order-2 md:order-1">
                                {{ $posts->firstItem() ?? 0 }}-{{ $posts->lastItem() ?? 0 }} / {{ $posts->total() }}
                            </div>

                            <div class="order-1 md:order-2">
                                {{ $posts->links('admin.vendor.pagination.kt') }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('page_js')
    <script>
        (function () {
            // ---------- Notify ----------
            function notify(type, text) {
                if (window.KTNotify && typeof KTNotify.show === 'function') {
                    KTNotify.show({ type, message: text, placement: 'top-end', duration: 1800 });
                    return;
                }
                if (window.Swal && Swal.mixin) {
                    Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1800,
                        timerProgressBar: true,
                    }).fire({ icon: type === 'error' ? 'error' : 'success', title: text });
                    return;
                }
                console.log(type.toUpperCase() + ': ' + text);
            }

            function csrfToken() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            }

            // ---------- Image popover ----------
            let popEl = null;

            function ensurePopover() {
                if (popEl) return popEl;
                popEl = document.createElement('div');
                popEl.style.position = 'fixed';
                popEl.style.zIndex = 9999;
                popEl.style.display = 'none';
                popEl.className = 'kt-card p-2 shadow-lg';
                popEl.innerHTML = `<img src="" style="width:220px;height:220px;object-fit:cover;border-radius:12px;">`;
                document.body.appendChild(popEl);
                return popEl;
            }

            function showImgPopover(anchor, imgUrl) {
                const el = ensurePopover();
                const img = el.querySelector('img');
                img.src = imgUrl;

                const r = anchor.getBoundingClientRect();
                const top = Math.min(window.innerHeight - 240, Math.max(10, r.top - 10));
                const left = Math.min(window.innerWidth - 240, Math.max(10, r.right + 12));

                el.style.top = top + 'px';
                el.style.left = left + 'px';
                el.style.display = 'block';
            }

            function hideImgPopover() {
                if (!popEl) return;
                popEl.style.display = 'none';
            }

            function initImagePopovers() {
                document.querySelectorAll('.js-img-popover').forEach((a) => {
                    if (a._inited) return;
                    a._inited = true;

                    const img = a.getAttribute('data-popover-img');
                    a.addEventListener('mouseenter', () => showImgPopover(a, img));
                    a.addEventListener('mouseleave', hideImgPopover);
                });
            }

            // ---------- Toggle publish ----------
            async function togglePublish(input) {
                const url = input.dataset.url;
                const row = input.closest('tr');
                const badgeWrap = row ? row.querySelector('.js-badge') : null;
                const publishedAt = row ? row.querySelector('.js-published-at') : null;

                const nextVal = input.checked ? 1 : 0;
                const rollback = !input.checked;

                input.disabled = true;
                row && row.classList.add('opacity-50');

                try {
                    const res = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: JSON.stringify({ is_published: nextVal }),
                    });

                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    const data = await res.json();
                    if (!data || !data.ok) throw new Error('Invalid response');

                    if (badgeWrap && data.badge_html) badgeWrap.innerHTML = data.badge_html;

                    if (publishedAt) {
                        if (data.is_published && data.published_at) {
                            publishedAt.textContent = 'Yayın: ' + data.published_at;
                        } else {
                            publishedAt.textContent = '';
                        }
                    }

                    notify('success', data.is_published ? 'Yayınlandı' : 'Taslağa alındı');
                } catch (e) {
                    input.checked = rollback;

                    const msg =
                        String(e.message).includes('HTTP 403') ? 'Yetkin yok (403).' :
                            String(e.message).includes('HTTP 419') ? 'Oturum/CSRF hatası (419).' :
                                'Durum güncellenemedi.';

                    notify('error', msg);
                    console.error(e);
                } finally {
                    input.disabled = false;
                    row && row.classList.remove('opacity-50');
                }
            }

            function initToggles() {
                document.querySelectorAll('.js-publish-toggle').forEach((cb) => {
                    if (cb._toggleInited) return;
                    cb._toggleInited = true;
                    cb.addEventListener('change', () => togglePublish(cb));
                });
            }

            // ---------- DataTables.net INIT ----------
            document.addEventListener('DOMContentLoaded', () => {

                initDataTable({
                    table: '#blog_table',
                    search: '#blogSearch',
                    pageSize: '#blogPageSize',
                    info: '#blogInfo',
                    pagination: '#blogPagination',

                    pageLength: 10,
                    lengthMenu: [5,10,25,50],
                    dom: 't',
                    order: [[0,'desc']],

                    emptyTemplate: '#dt-empty-blog',
                    zeroTemplate: '#dt-zero-blog',

                    columnDefs: [
                        { orderable:false, searchable:false, targets:[5,6] }, // işlem kolonları
                        { className:'text-right', targets:[5,6] },
                    ],

                    onDraw: () => {
                        initImagePopovers();
                        // toggle için DELEGATION kullanıyorsan burada initToggles çağırmana bile gerek yok
                        initToggles();
                    }
                });

                initImagePopovers();
                initToggles();
                const sel = document.getElementById('blogCategoryFilter');
                if (!sel) return;

                // Select2 varsa kullan
                if (window.$ && $.fn && $.fn.select2) {
                    $(sel).select2({
                        width: '260px',
                        placeholder: 'Kategori filtrele',
                        closeOnSelect: false,
                        allowClear: true
                    }).on('change', function(){
                        // admin UX: seçince otomatik filtre uygula
                        this.closest('form')?.requestSubmit();
                    });
                } else {
                    // Select2 yoksa yine otomatik submit
                    sel.addEventListener('change', () => {
                        sel.closest('form')?.requestSubmit();
                    });
                }
                const sel1 = document.getElementById('blogPageSize');
                const form = document.querySelector('form[action="{{ route('admin.blog.index') }}"]');

                if (!sel1 || !form) return;

                const options = [10,25,50,100];
                sel1.innerHTML = options.map(v => `<option value="${v}">${v}</option>`).join('');
                sel.value = "{{ $perPage ?? 25 }}";

                sel1.addEventListener('change', () => {
                    // hidden perpage güncelle
                    let hidden = form.querySelector('input[name="perpage"]');
                    if (!hidden) {
                        hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'perpage';
                        form.appendChild(hidden);
                    }
                    hidden.value = sel1.value;
                    form.requestSubmit();
                });
            });

            // ---------- Custom pagination (Metronic footer içine) ----------
            function renderPagination(api) {
                const host = document.querySelector('#blogPagination');
                if (!host) return;

                const info = api.page.info();
                const pages = info.pages;
                const page = info.page;

                host.innerHTML = '';
                if (pages <= 1) return;

                const makeBtn = (label, targetPage, disabled = false, active = false) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = active ? 'kt-btn kt-btn-sm kt-btn-primary' : 'kt-btn kt-btn-sm kt-btn-light';
                    if (disabled) btn.disabled = true;
                    btn.textContent = label;
                    btn.addEventListener('click', () => api.page(targetPage).draw('page'));
                    return btn;
                };

                host.appendChild(makeBtn('‹', Math.max(0, page - 1), page === 0));

                const start = Math.max(0, page - 2);
                const end = Math.min(pages - 1, page + 2);
                for (let i = start; i <= end; i++) host.appendChild(makeBtn(String(i + 1), i, false, i === page));

                host.appendChild(makeBtn('›', Math.min(pages - 1, page + 1), page === pages - 1));
            }

        })();
    </script>
@endpush
