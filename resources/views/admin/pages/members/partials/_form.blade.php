@php
    $isActive = (bool) old('is_active', $member->is_active ?? true);
@endphp

<div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.25fr)_380px]">
    <div class="grid gap-6">
        <div class="kt-card">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Temel Bilgiler</h3>
                    <div class="text-sm text-muted-foreground">
                        Üyenin iletişim ve hesap bilgilerini güncelle.
                    </div>
                </div>
            </div>

            <div class="kt-card-content grid gap-5 p-6">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="member_name">Ad</label>
                        <input id="member_name" name="name" class="kt-input @error('name') kt-input-invalid @enderror" value="{{ old('name', $member->name) }}">
                        @error('name')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label" for="member_surname">Soyad</label>
                        <input id="member_surname" name="surname" class="kt-input @error('surname') kt-input-invalid @enderror" value="{{ old('surname', $member->surname) }}">
                        @error('surname')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="member_email">E-posta</label>
                        <input id="member_email" type="email" name="email" class="kt-input @error('email') kt-input-invalid @enderror" value="{{ old('email', $member->email) }}">
                        @error('email')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label" for="member_phone">Telefon</label>
                        <input id="member_phone" name="phone" class="kt-input @error('phone') kt-input-invalid @enderror" value="{{ old('phone', $member->phone) }}">
                        @error('phone')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="member_password">Yeni Şifre</label>
                        <input id="member_password" type="password" name="password" class="kt-input @error('password') kt-input-invalid @enderror" placeholder="Boş bırakırsan değişmez">
                        @error('password')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label" for="member_password_confirmation">Yeni Şifre Tekrar</label>
                        <input id="member_password_confirmation" type="password" name="password_confirmation" class="kt-input" placeholder="Şifreyi tekrar gir">
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Belge Yönetimi</h3>
                    <div class="text-sm text-muted-foreground">
                        Üyenin yüklediği dosyayı görüntüle veya yeni belge yükle.
                    </div>
                </div>
            </div>

            <div class="kt-card-content grid gap-5 p-6">
                <div class="grid gap-2">
                    <label class="kt-form-label" for="member_filepath">Yeni Belge Yükle</label>
                    <input id="member_filepath" type="file" name="filepath" class="kt-input @error('filepath') kt-input-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                    <div class="text-xs text-muted-foreground">PDF, JPG, PNG, WEBP, DOC ve DOCX desteklenir. Maksimum 12 MB.</div>
                    @error('filepath')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                @if($member->hasDocument())
                    <label class="flex items-start gap-3 rounded-2xl border border-border bg-muted/20 px-4 py-4">
                        <input type="hidden" name="clear_document" value="0">
                        <input type="checkbox" name="clear_document" value="1" class="kt-checkbox mt-1">
                        <span>
                            <span class="block font-medium text-foreground">Mevcut belgeyi kaldır</span>
                            <span class="text-sm text-muted-foreground">Yeni yükleme yapmadan işaretlersen mevcut belge tamamen silinir.</span>
                        </span>
                    </label>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 self-start xl:sticky xl:top-6">
        <div class="kt-card">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Üyelik Durumu</h3>
                    <div class="text-sm text-muted-foreground">
                        Hesabın aktifliğini ve askı notunu yönet.
                    </div>
                </div>
            </div>

            <div class="kt-card-content grid gap-4 p-6">
                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-medium text-foreground">Aktif üyelik</div>
                            <div class="text-sm text-muted-foreground">Pasif üyeler giriş yapamaz.</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_active" value="0">
                            <label class="kt-switch kt-switch-sm">
                                <input type="checkbox" name="is_active" value="1" class="kt-switch" @checked($isActive)>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label" for="member_suspension_reason">Askı Notu</label>
                    <textarea id="member_suspension_reason" name="suspension_reason" rows="4" class="kt-textarea @error('suspension_reason') kt-input-invalid @enderror" placeholder="Pasife alma nedeni veya iç operasyon notu">{{ old('suspension_reason', $member->suspension_reason) }}</textarea>
                    @error('suspension_reason')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="rounded-3xl app-surface-card p-5 text-sm text-muted-foreground">
            <div class="font-medium text-foreground">Kayıt Bilgileri</div>
            <div class="mt-3 grid gap-2">
                <div>No: #{{ $member->id }}</div>
                <div>Oluşturulma: {{ optional($member->created_at)->format('d.m.Y H:i') ?: '-' }}</div>
                <div>Son Güncelleme: {{ optional($member->updated_at)->format('d.m.Y H:i') ?: '-' }}</div>
                <div>Son Giriş: {{ optional($member->last_login_at)->format('d.m.Y H:i') ?: 'Henüz giriş yok' }}</div>
                <div>E-posta Doğrulama: {{ optional($member->email_verified_at)->format('d.m.Y H:i') ?: 'Doğrulanmadı' }}</div>
            </div>
        </div>
    </div>
</div>
