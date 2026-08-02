(function () {
  const ready = (callback) => {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback, { once: true });
      return;
    }

    callback();
  };

  function initViewportAnimations() {
    const items = [...document.querySelectorAll('.et-in-viewport-check')];
    if (!items.length) return;

    const run = (item) => {
      const name = item.getAttribute('et-anim');
      const duration = Number(item.getAttribute('et-anim-duration') || 1000);
      const delay = Number(item.getAttribute('et-anim-delay') || 0);
      const easing = item.getAttribute('et-anim-easing') || 'ease';

      transitionTimer = window.setTimeout(() => {
        item.classList.add(`et-in-viewport-${name}`);
        item.style.animation = `${name} ${duration}ms ${easing} forwards`;
      }, delay);
    };

    if (!('IntersectionObserver' in window)) {
      items.forEach(run);
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        run(entry.target);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.1 });

    items.forEach((item) => observer.observe(item));
  }

  function initTooltips() {
    const buttons = [...document.querySelectorAll('.tooltip-item[data-header-text]')];
    const panels = [...document.querySelectorAll('.content-before-after .content-left li')];
    if (!buttons.length || !panels.length) return;

    const activate = (key) => {
      panels.forEach((panel) => {
        panel.classList.toggle('active', panel.classList.contains(key));
      });

      buttons.forEach((button) => {
        button.classList.toggle('is-active', button.dataset.headerText === key);
      });
    };

    buttons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.stopPropagation();
        activate(button.dataset.headerText);
      });
    });
  }

  function initStickyHeader() {
    const header = document.getElementById('header-wrapper');
    if (!header) return;

    const sync = () => {
      const canStick = document.documentElement.scrollHeight - window.innerHeight > header.offsetHeight * 3;
      header.classList.toggle('sticky', canStick && window.scrollY > header.offsetHeight);
    };

    sync();
    window.addEventListener('scroll', sync, { passive: true });
    window.addEventListener('resize', sync);
  }

  function initHomeModes() {
    const body = document.body;
    const tabs = [...document.querySelectorAll('[data-home-mode-tab]')];
    const titles = [...document.querySelectorAll('[data-home-hero-title]')];
    const links = [...document.querySelectorAll('[data-home-cta]')];
    if (tabs.length < 2 || !titles.length || !links.length) return;

    let transitionTimer = 0;

    const payloadFor = (tab) => {
      try {
        return JSON.parse(tab.dataset.homeModePayload || '{}');
      } catch {
        return {};
      }
    };

    const activate = (tab) => {
      const payload = payloadFor(tab);
      if (!payload.key) return;

      tabs.forEach((item) => {
        const active = item === tab;
        item.classList.toggle('is-active', active);
        item.setAttribute('aria-selected', active ? 'true' : 'false');
        item.tabIndex = active ? 0 : -1;
      });

      window.clearTimeout(transitionTimer);
      body.classList.add('home-mode-changing');

      window.setTimeout(() => {
        body.dataset.homeMode = payload.key;

        Object.entries(payload.styles || {}).forEach(([property, value]) => {
          if (property.startsWith('--home-') && typeof value === 'string') {
            body.style.setProperty(property, value);
          }
        });

        titles.forEach((title) => {
          title.textContent = payload.hero_title || '';
        });

        links.forEach((link) => {
          link.href = payload.cta_url || '#';
          link.title = payload.cta_label || '';
          const label = link.querySelector('[data-home-cta-label]');
          if (label) label.textContent = payload.cta_label || '';
        });

        window.dispatchEvent(new CustomEvent('home:modechange', { detail: payload }));

        transitionTimer = window.setTimeout(() => {
          body.classList.remove('home-mode-changing');
        }, 170);
      }, 90);
    };

    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => activate(tab));
      tab.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;

        event.preventDefault();
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        const next = tabs[(index + direction + tabs.length) % tabs.length];
        next.focus();
        activate(next);
      });
    });
  }

  function initBeforeAfter() {
    const root = document.getElementById('before-after');
    const handle = document.getElementById('dragme');
    const after = document.querySelector('[data-after-view]');
    const afterWrapper = document.querySelector('.wrapper-after');
    const headerItems = [...document.querySelectorAll('[data-home-header-contrast]')];

    if (!root || !handle || !after || !afterWrapper) return;

    let ratio = 0.5;
    let dragging = false;

    const bounds = () => root.getBoundingClientRect();

    const moveTo = (clientX) => {
      const rect = bounds();
      const x = Math.min(Math.max(clientX - rect.left, 0), rect.width);
      ratio = rect.width ? x / rect.width : 0.5;

      after.style.width = `${x}px`;
      handle.style.left = `${x}px`;
      root.style.setProperty('--home-split-position', `${ratio * 100}%`);
      handle.setAttribute('aria-valuenow', String(Math.round(ratio * 100)));

      const splitX = rect.left + x;
      headerItems.forEach((item) => {
        const itemRect = item.getBoundingClientRect();
        const onAfter = itemRect.left + itemRect.width / 2 <= splitX;
        item.classList.toggle('on-after', onAfter);
        item.classList.toggle('on-before', !onAfter);
      });
    };

    const moveToRatio = () => {
      const rect = bounds();
      afterWrapper.style.width = `${window.innerWidth}px`;
      moveTo(rect.left + rect.width * ratio);
    };

    const animateTo = (clientX) => {
      after.style.transition = 'width 0.65s ease';
      handle.style.transition = 'left 0.65s ease';
      moveTo(clientX);
      window.setTimeout(() => {
        after.style.transition = '';
        handle.style.transition = '';
      }, 680);
    };

    handle.addEventListener('pointerdown', (event) => {
      dragging = true;
      handle.setPointerCapture?.(event.pointerId);
      moveTo(event.clientX);
    });

    window.addEventListener('pointermove', (event) => {
      if (!dragging) return;
      moveTo(event.clientX);
    });

    window.addEventListener('pointerup', () => {
      dragging = false;
    });

    root.addEventListener('click', (event) => {
      if (event.target.closest('.tooltip-item, .btn-header, #dragme')) return;
      animateTo(event.clientX);
    });

    handle.addEventListener('keydown', (event) => {
      const step = event.shiftKey ? 0.1 : 0.03;

      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        ratio = Math.max(0, ratio - step);
        moveToRatio();
      }

      if (event.key === 'ArrowRight') {
        event.preventDefault();
        ratio = Math.min(1, ratio + step);
        moveToRatio();
      }

      if (event.key === 'Home') {
        event.preventDefault();
        ratio = 0;
        moveToRatio();
      }

      if (event.key === 'End') {
        event.preventDefault();
        ratio = 1;
        moveToRatio();
      }
    });

    window.addEventListener('resize', moveToRatio);
    moveToRatio();
  }

  function initPageExitTransition() {
    const body = document.body;
    const links = [...document.querySelectorAll('[data-home-cta]')];
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!links.length || reduceMotion) return;

    let leaving = false;

    links.forEach((link) => {
      link.addEventListener('click', (event) => {
        if (
          event.defaultPrevented
          || event.button !== 0
          || event.metaKey
          || event.ctrlKey
          || event.shiftKey
          || event.altKey
          || link.target === '_blank'
          || link.hasAttribute('download')
        ) {
          return;
        }

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#')) return;

        let destination;

        try {
          destination = new URL(link.href, window.location.href);
        } catch {
          return;
        }

        if (!['http:', 'https:'].includes(destination.protocol) || destination.href === window.location.href) {
          return;
        }

        event.preventDefault();
        if (leaving) return;

        leaving = true;
        body.classList.remove('home-mode-changing');
        body.classList.add('home-leaving');
        window.dispatchEvent(new CustomEvent('home:leaving'));

        window.setTimeout(() => {
          window.location.assign(destination.href);
        }, 560);
      });
    });

    window.addEventListener('pageshow', () => {
      leaving = false;
      body.classList.remove('home-leaving');
    });
  }

  function initStatParticles() {
    const body = document.body;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const coarsePointer = window.matchMedia('(pointer: coarse)').matches;

    if (body.dataset.statSymbols !== 'true' || reduceMotion || coarsePointer) return;

    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    if (!context) return;

    canvas.className = 'home-stat-particles';
    canvas.setAttribute('aria-hidden', 'true');
    body.appendChild(canvas);

    const symbols = ['∑', '%', 'π', 'σ', '±', '√', '∞', '↗'];
    const particles = [];
    const handle = document.getElementById('dragme');
    const mode = body.dataset.statSymbolMode === 'moving' ? 'moving' : 'idle';
    let frameId = 0;
    let idleTimer = 0;
    let burstTimer = 0;
    let pointerX = 0;
    let pointerY = 0;
    let lastMovingEmitAt = 0;
    let lastMovingX = 0;
    let lastMovingY = 0;
    let viewportWidth = window.innerWidth;
    let viewportHeight = window.innerHeight;
    let pixelRatio = 1;

    const random = (min, max) => min + Math.random() * (max - min);

    const resize = () => {
      viewportWidth = window.innerWidth;
      viewportHeight = window.innerHeight;
      pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
      canvas.width = Math.round(viewportWidth * pixelRatio);
      canvas.height = Math.round(viewportHeight * pixelRatio);
      context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
    };

    const colorAt = (x) => {
      const split = handle?.getBoundingClientRect().left ?? viewportWidth / 2;
      const styles = getComputedStyle(body);
      const beforeColor = styles.getPropertyValue('--home-stat-before').trim() || '#ec6367';
      const afterColor = styles.getPropertyValue('--home-stat-after').trim() || '#ffffff';
      return x <= split ? afterColor : beforeColor;
    };

    const draw = (now) => {
      context.clearRect(0, 0, viewportWidth, viewportHeight);

      for (let index = particles.length - 1; index >= 0; index -= 1) {
        const particle = particles[index];
        const age = now - particle.createdAt;
        const progress = age / particle.duration;

        if (progress >= 1) {
          particles.splice(index, 1);
          continue;
        }

        const elapsed = age / 1000;
        const curve = Math.sin(particle.phase + progress * 5) * particle.curve * progress;
        const x = particle.x + particle.velocityX * elapsed + curve;
        const y = particle.y + particle.velocityY * elapsed - curve * 0.35;
        const fadeIn = Math.min(progress / 0.12, 1);
        const opacity = fadeIn * Math.pow(1 - progress, 1.45) * particle.opacity;
        const scale = 0.62 + Math.sin(Math.PI * progress) * 0.42 - progress * 0.16;

        context.save();
        context.translate(x, y);
        context.rotate(particle.rotation + particle.spin * elapsed);
        context.scale(scale, scale);
        context.globalAlpha = opacity;
        context.fillStyle = particle.color;
        context.font = `600 ${particle.size}px "Segoe UI", sans-serif`;
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillText(particle.symbol, 0, 0);
        context.restore();
      }

      frameId = particles.length ? window.requestAnimationFrame(draw) : 0;
    };

    const emit = (x, y) => {
      const now = performance.now();
      const count = Math.floor(random(2, 5));
      const baseAngle = random(0, Math.PI * 2);
      const color = colorAt(x);

      for (let index = 0; index < count; index += 1) {
        const angle = baseAngle + (Math.PI * 2 * index) / count + random(-0.42, 0.42);
        const speed = random(42, 92);
        const originRadius = random(1, 6);

        particles.push({
          symbol: symbols[Math.floor(Math.random() * symbols.length)],
          x: x + Math.cos(angle) * originRadius,
          y: y + Math.sin(angle) * originRadius,
          color,
          createdAt: now,
          duration: random(720, 1180),
          velocityX: Math.cos(angle) * speed,
          velocityY: Math.sin(angle) * speed,
          curve: random(3, 9),
          phase: random(0, Math.PI * 2),
          rotation: random(-0.45, 0.45),
          spin: random(-1.1, 1.1),
          opacity: random(0.2, 0.36),
          size: random(12, 18),
        });
      }

      if (particles.length > 44) particles.splice(0, particles.length - 44);
      if (!frameId) frameId = window.requestAnimationFrame(draw);
    };

    const clearParticles = () => {
      particles.length = 0;

      if (frameId) {
        window.cancelAnimationFrame(frameId);
        frameId = 0;
      }

      context.clearRect(0, 0, viewportWidth, viewportHeight);
    };

    const stopIdleEmission = () => {
      window.clearTimeout(idleTimer);
      window.clearInterval(burstTimer);
      idleTimer = 0;
      burstTimer = 0;
      clearParticles();
    };

    const startIdleEmission = () => {
      emit(pointerX, pointerY);
      burstTimer = window.setInterval(() => emit(pointerX, pointerY), 260);
    };

    const handlePointerMove = (event) => {
      if (event.pointerType === 'touch') return;

      pointerX = event.clientX;
      pointerY = event.clientY;

      if (mode === 'moving') {
        const now = performance.now();
        const distance = Math.hypot(pointerX - lastMovingX, pointerY - lastMovingY);

        if (now - lastMovingEmitAt < 68 || distance < 8) return;

        lastMovingEmitAt = now;
        lastMovingX = pointerX;
        lastMovingY = pointerY;
        emit(pointerX, pointerY);

        return;
      }

      stopIdleEmission();
      idleTimer = window.setTimeout(startIdleEmission, 220);
    };

    resize();
    window.addEventListener('resize', resize, { passive: true });
    window.addEventListener('pointermove', handlePointerMove, { passive: true });
    window.addEventListener('blur', stopIdleEmission);
    window.addEventListener('home:modechange', clearParticles);
    window.addEventListener('home:leaving', clearParticles);
    document.documentElement.addEventListener('pointerleave', stopIdleEmission);
  }

  ready(() => {
    initViewportAnimations();
    initTooltips();
    initStickyHeader();
    initHomeModes();
    initBeforeAfter();
    initPageExitTransition();
    initStatParticles();
  });
})();
