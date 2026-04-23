@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="members.index">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Üyelik Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">Üye Havuzu</h1>
                    <div class="text-sm text-muted-foreground">
                        Site üyelerini, yükledikleri belgeleri ve durumlarını tek panelden yönetin.
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam Üye</div>
                <div class="mt-2 text-3xl font-semibold">{{ $stats['all'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Aktif</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Askıda</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['suspended'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Belge Yüklü</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['documents'] ?? 0 }}</div>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Üye Listesi</h3>
                    <div class="text-sm text-muted-foreground">
                        Kayıtları ara, duruma göre filtrele ve üye profiline geç.
                    </div>
                </div>

                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        class="kt-input w-full md:w-[280px]"
                        placeholder="Ad, soyad, e-posta veya telefon ara"
                    >

                    <select name="status" class="kt-select w-full md:w-[220px]" data-kt-select="true">
                        <option value="all" @selected($status === 'all')>Tüm durumlar</option>
                        <option value="active" @selected($status === 'active')>Aktif üyeler</option>
                        <option value="suspended" @selected($status === 'suspended')>Askıdaki üyeler</option>
                        <option value="document" @selected($status === 'document')>Belgesi olan üyeler</option>
                    </select>

                    <button type="submit" class="kt-btn kt-btn-light">Filtrele</button>
                </form>
            </div>

            <div class="kt-card-content p-6">
                @if($members->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead>
                            <tr class="text-left text-muted-foreground">
                                <th class="px-4 py-3 font-medium">Üye</th>
                                <th class="px-4 py-3 font-medium">Durum</th>
                                <th class="px-4 py-3 font-medium">Belge</th>
                                <th class="px-4 py-3 font-medium">Randevu</th>
                                <th class="px-4 py-3 font-medium">Mesaj</th>
                                <th class="px-4 py-3 font-medium">Son Giriş</th>
                                <th class="px-4 py-3 font-medium">Kayıt</th>
                                <th class="px-4 py-3 font-medium text-right">İşlemler</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                            @foreach($members as $member)
                                <tr>
                                    <td class="px-4 py-4 align-top">
                                        <div class="font-semibold text-foreground">{{ $member->full_name ?: '-' }}</div>
                                        <div class="mt-1 text-muted-foreground">{{ $member->email }}</div>
                                        <div class="mt-1 text-xs text-muted-foreground">{{ $member->phone ?: 'Telefon yok' }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="{{ $member->statusBadgeClass() }}">{{ $member->statusLabel() }}</span>
                                        @if($member->isSuspended() && $member->suspension_reason)
                                            <div class="mt-2 max-w-[220px] text-xs text-muted-foreground">{{ $member->suspension_reason }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        @if($member->hasDocument())
                                            <div class="font-medium text-foreground">{{ $member->documentName() }}</div>
                                            <div class="mt-1 text-xs text-muted-foreground">{{ $member->documentSizeLabel() ?: 'Boyut yok' }}</div>
                                        @else
                                            <span class="text-muted-foreground">Belge yok</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 align-top text-foreground">{{ $member->appointments_count }}</td>
                                    <td class="px-4 py-4 align-top text-foreground">{{ $member->contact_messages_count }}</td>
                                    <td class="px-4 py-4 align-top text-muted-foreground">
                                        {{ optional($member->last_login_at)->format('d.m.Y H:i') ?: 'Henüz giriş yok' }}
                                    </td>
                                    <td class="px-4 py-4 align-top text-muted-foreground">
                                        {{ optional($member->created_at)->format('d.m.Y H:i') ?: '-' }}
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.members.show', $member) }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                                İncele
                                            </a>

                                            <form method="POST" action="{{ route('admin.members.toggleStatus', $member) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-light">
                                                    {{ $member->is_active ? 'Askıya Al' : 'Aktif Et' }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.members.destroy', $member) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-danger" onclick="return confirm('Bu üyelik kaydı silinsin mi?')">
                                                    Sil
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">
                        {{ $members->links() }}
                    </div>
                @else
                    <div class="rounded-3xl border border-dashed border-border px-6 py-12 text-center">
                        <div class="text-lg font-semibold">Henüz üye kaydı yok.</div>
                        <div class="mt-2 text-sm text-muted-foreground">
                            Site kayıt formu kullanılmaya başladığında üyeler burada listelenecek.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
