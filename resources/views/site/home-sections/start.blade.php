<section id="birlikte-baslayalim" class="home-start-section" aria-labelledby="home-start-title">
    <div class="home-feature-container home-start-section__inner">
        <div class="home-start-section__copy et-in-viewport-check" et-anim="feature-rise" et-anim-duration="620">
            <span class="home-feature-eyebrow">Birlikte Başlayalım</span>
            <h2 id="home-start-title">Araştırmanız için doğru adımı birlikte planlayalım.</h2>
            <p>Önce hesabınızı oluşturun; ardından çalışma alanınızdan destek konusunu, uzman tercihinizi ve uygun görüşme saatini seçin.</p>
            <div class="home-start-section__actions">
                @if($hasActiveMemberSession)
                    <a href="{{ route('member.appointments.index', ['site_locale' => $locale]) }}" class="home-start-section__primary">Randevu Planla <span aria-hidden="true">→</span></a>
                    <a href="{{ route('member.account.show', ['site_locale' => $locale]) }}" class="home-start-section__secondary">Çalışma Alanım</a>
                @else
                    <a href="{{ route('member.register', ['site_locale' => $locale]) }}" class="home-start-section__primary">Kayıt Ol <span aria-hidden="true">→</span></a>
                    <a href="{{ route('member.login', ['site_locale' => $locale]) }}" class="home-start-section__secondary">Giriş Yap</a>
                @endif
            </div>

            @include('site.home-sections.partials.page-link', [
                'url' => route('site.contact-messages.create', ['site_locale' => $locale]),
                'label' => 'İletişim',
                'eyebrow' => 'Sorunuz mu var?',
                'variant' => 'start',
            ])
        </div>

        <ol class="home-start-steps" aria-label="Başlangıç adımları">
            <li><span>01</span><strong>Hesabınızı oluşturun</strong><small>İletişim ve kurum bilgilerinizi güvenli biçimde kaydedin.</small></li>
            <li><span>02</span><strong>Ön görüşmeyi planlayın</strong><small>Destek konusu, uzman ve uygun zamanı seçin.</small></li>
            <li><span>03</span><strong>Süreci takip edin</strong><small>Dosyalarınızı, analiz aşamalarını ve sonuçlarınızı tek yerden izleyin.</small></li>
        </ol>
    </div>
</section>
