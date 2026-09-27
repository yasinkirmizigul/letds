const motionPreference = () => window.matchMedia('(prefers-reduced-motion: reduce)');
const clamp = (value, min = 0, max = 1) => Math.min(max, Math.max(min, value));
const smoothstep = (value) => { const t = clamp(value); return t * t * (3 - 2 * t); };

function splitTeamHeading(element) {
    const text = element.textContent.trim();
    element.setAttribute('aria-label', text);
    element.textContent = '';
    let index = 0;
    text.split(/\s+/).forEach((word, wordIndex) => {
        if (wordIndex) element.append(' ');
        const group = document.createElement('span');
        group.className = 'site-team__word';
        group.setAttribute('aria-hidden', 'true');
        [...word].forEach((letter) => {
            const clip = document.createElement('span');
            clip.className = 'site-team__letter';
            const glyph = document.createElement('span');
            glyph.textContent = letter;
            glyph.style.setProperty('--letter-delay', `${Math.min(index++, 32) * 18}ms`);
            clip.append(glyph);
            group.append(clip);
        });
        element.append(group);
    });
}

export function initTeamCarousel() {
    document.querySelectorAll('[data-site-team]').forEach((section) => {
        const track = section.querySelector('[data-site-team-track]');
        const originals = [...section.querySelectorAll('[data-site-team-card]')];
        const panels = [...section.querySelectorAll('[data-site-team-panel]')];
        const count = section.querySelector('[data-site-team-count]');
        if (!track || !originals.length) return;
        const preference = motionPreference();
        panels.forEach((panel) => panel.querySelectorAll('h3, .site-team__role').forEach(splitTeamHeading));
        // Only portraits repeat. Each expert has one accessible information panel.
        const cards = [...originals];
        if (originals.length > 1) {
            while (cards.length < 9 || cards.length % originals.length !== 0) {
                const clone = originals[cards.length % originals.length].cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                clone.removeAttribute('tabindex');
                clone.removeAttribute('role');
                track.append(clone);
                cards.push(clone);
            }
        }
        let current = 0;
        let frame = 0;
        let startX = null;
        let moved = false;
        let visible = false;
        let scrollLift = 0;
        const modulo = (value, size) => ((value % size) + size) % size;
        const arrange = (animate = true) => {
            const cardWidth = originals[0].offsetWidth;
            const gap = clamp(track.clientWidth * .045, 24, 64);
            cards.forEach((card, index) => {
                const offset = modulo(index - current + Math.floor(cards.length / 2), cards.length) - Math.floor(cards.length / 2);
                const wrapped = Math.abs(offset - Number(card.dataset.offset || 0)) > 2;
                card.style.transitionDuration = !animate || wrapped || preference.matches ? '0ms' : '';
                card.style.setProperty('--card-x', `${offset * (cardWidth + gap)}px`);
                card.dataset.offset = String(offset);
                card.classList.toggle('is-active', offset === 0);
                card.style.zIndex = String(10 - Math.abs(offset));
            });
            const active = modulo(current, originals.length);
            panels.forEach((panel, index) => {
                panel.classList.toggle('is-active', index === active);
                panel.setAttribute('aria-hidden', String(index !== active));
                panel.inert = index !== active;
            });
            if (count) count.textContent = `${String(active + 1).padStart(2, '0')} / ${String(originals.length).padStart(2, '0')}`;
        };
        const advance = (direction) => { current = modulo(current + direction, cards.length); arrange(); };
        section.querySelector('[data-site-team-prev]')?.addEventListener('click', () => advance(-1));
        section.querySelector('[data-site-team-next]')?.addEventListener('click', () => advance(1));
        cards.forEach((card) => card.addEventListener('click', () => {
            if (!moved) advance(Number(card.dataset.offset));
        }));
        track.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Enter', ' '].includes(event.key)) return;
            event.preventDefault();
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') advance(event.key === 'ArrowRight' ? 1 : -1);
            else if (event.target.matches('[data-site-team-card]')) advance(Number(event.target.dataset.offset));
        });
        track.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) return;
            startX = event.clientX;
            moved = false;
        });
        track.addEventListener('pointermove', (event) => {
            if (startX !== null && Math.abs(event.clientX - startX) > 12) moved = true;
        });
        window.addEventListener('pointerup', (event) => {
            if (startX === null) return;
            const distance = event.clientX - startX;
            startX = null;
            if (Math.abs(distance) > 45) advance(distance > 0 ? -1 : 1);
        });
        track.addEventListener('pointercancel', () => { startX = null; });
        const updateScroll = () => {
            frame = 0;
            const rect = section.getBoundingClientRect();
            const target = preference.matches ? 0 : clamp((rect.top - window.innerHeight * .18) * .16, -55, 100);
            scrollLift += (target - scrollLift) * .12;
            if (preference.matches) scrollLift = 0;
            section.style.setProperty('--team-scroll-y', `${scrollLift.toFixed(2)}px`);
            if (visible && Math.abs(target - scrollLift) > .1) frame = requestAnimationFrame(updateScroll);
        };
        const schedule = () => { if (visible && !frame) frame = requestAnimationFrame(updateScroll); };
        new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            if (visible) { section.classList.add('is-in-view'); schedule(); }
        }, { rootMargin: '80px' }).observe(section);
        window.addEventListener('scroll', schedule, { passive: true });
        new ResizeObserver(() => { arrange(false); schedule(); }).observe(track);
        preference.addEventListener('change', () => { arrange(false); schedule(); });
        section.classList.add('is-enhanced');
        arrange(false);
    });
}

export function initServicesParticleLogo() {
    const root = document.querySelector('[data-site-particle-logo]');
    const canvas = root?.querySelector('[data-site-particle-canvas]');
    const context = canvas?.getContext('2d');
    if (!root || !canvas || !context) return;
    const hero = root.closest('.site-services-hero');
    const preference = motionPreference();
    const logo = new Image();
    logo.decoding = 'async';
    logo.addEventListener('load', () => {
        const source = document.createElement('canvas');
        source.width = source.height = 420;
        const sourceContext = source.getContext('2d', { willReadFrequently: true });
        if (!sourceContext) return;
        sourceContext.drawImage(logo, 0, 0, 420, 420);
        const pixels = sourceContext.getImageData(0, 0, 420, 420).data;
        const filled = (x, y) => x >= 0 && y >= 0 && x < 420 && y < 420 && pixels[(Math.floor(y) * 420 + Math.floor(x)) * 4 + 3] > 105;
        const colors = [1, 2, 3, 4].map((n) => getComputedStyle(root).getPropertyValue(`--symbol-${n}`).trim());
        let dark = document.documentElement.dataset.siteTheme === 'dark';
        const blend = (color, target, ratio) => {
            const hex = color.replace('#', '');
            const targetHex = target.replace('#', '');
            if (!/^[\da-f]{6}$/i.test(hex)) return color;
            const channels = [0, 2, 4].map((offset) => Math.round(
                parseInt(hex.slice(offset, offset + 2), 16) * (1 - ratio)
                + parseInt(targetHex.slice(offset, offset + 2), 16) * ratio,
            ));
            return `#${channels.map((channel) => channel.toString(16).padStart(2, '0')).join('')}`;
        };
        // Rasterize translucent outlines once per theme; color controls remain dashboard-owned.
        const sprites = ['Σ', 'μ', 'σ', 'x̄'].map(() => {
            const sprite = document.createElement('canvas');
            sprite.width = sprite.height = 96;
            return sprite;
        });
        const paintSprites = () => sprites.forEach((sprite, index) => {
            const symbol = ['Σ', 'μ', 'σ', 'x̄'][index];
            const brush = sprite.getContext('2d');
            const color = dark ? colors[index] : blend(colors[index], '#1b3957', .55);
            brush.clearRect(0, 0, 96, 96);
            brush.font = '600 63px Georgia, serif';
            brush.textAlign = 'center';
            brush.textBaseline = 'middle';
            brush.lineJoin = 'round';
            brush.lineWidth = 3.4;
            brush.globalAlpha = 1;
            brush.strokeStyle = color;
            brush.strokeText(symbol, 48, 49);
            brush.globalAlpha = .18;
            brush.fillStyle = color;
            brush.fillText(symbol, 48, 49);
        });
        paintSprites();
        const particles = [];
        const dust = [];
        const featured = new Set();
        const pointer = { x: -1000, y: -1000, targetX: -1000, targetY: -1000, dx: 0, dy: 0, velocityX: 0, velocityY: 0, lastEvent: 0, active: false };
        let width = 1, height = 1, scale = 1, frame = 0, lastTime = 0, elapsed = 0;
        let scroll = 0, tiltX = 0, tiltY = 0;
        let ratio = 1, scrollLift = 0, previousSortAngle = 10;
        let geometry = { left: 0, top: 0, heroTop: 0, heroHeight: 1 };
        let visible = false;
        let entrance = preference.matches ? 1 : 0;
        const random = (seed) => { const value = Math.sin(seed * 127.1 + 311.7) * 43758.5453; return value - Math.floor(value); };
        const buildParticles = () => {
            particles.length = 0;
            featured.clear();
            let index = 0;
            const step = width < 420 ? 8 : 6;
            for (let y = 3; y < 417; y += step) {
                for (let x = 3; x < 417; x += step) {
                    if (!filled(x, y)) continue;
                    const edge = !filled(x - step, y) || !filled(x + step, y) || !filled(x, y - step) || !filled(x, y + step);
                    for (let layer = 0; layer < (edge ? 3 : 2); layer++) {
                        const seed = index++;
                        const z = edge ? -24 + layer * 24 : (layer ? 24 : -24);
                        const colorFlow = Math.sin(x * .027 + Math.sin(y * .018) * 2.2) + Math.cos(y * .035 - x * .014);
                        const colorBand = Math.floor(clamp((colorFlow + 2) / 4, 0, .999) * 4);
                        particles.push({
                            x: (x - 210 + (random(seed) - .5) * step) / 420,
                            y: (y - 210 + (random(seed + 3) - .5) * step) / 420,
                            z: (z + Math.sin(x * .028) * Math.cos(y * .03) * 6) / 420,
                            kind: random(seed + 5) < .25 ? Math.floor(random(seed + 6) * 4) : colorBand,
                            size: 10 + random(seed + 8) * 11,
                            phase: random(seed + 11) * Math.PI * 2, spin: (random(seed + 13) - .5) * .6,
                            spreadX: (random(seed + 17) - .5) * 1.8,
                            spreadY: (random(seed + 19) - .5) * 1.5,
                            spreadZ: (random(seed + 23) - .5) * .8,
                            hover: 0, offsetX: 0, offsetY: 0, drawSize: 0, proximity: 0, distance: 0, px: 0, py: 0, depth: 0, perspective: 1,
                        });
                    }
                }
            }
            dust.length = 0;
            for (let i = 0; i < 22; i++) dust.push({ x: random(i + 71), y: random(i + 111), z: random(i + 193), phase: random(i + 221) * 6.28, kind: i % 4 });
        };
        const render = (delta = 1) => {
            const reduced = preference.matches;
            const ease = (rate) => 1 - Math.exp(-rate * delta);
            // Scroll runs this sequence in both directions: turn, drift, release, reassemble.
            const targetScroll = reduced ? 0 : clamp((window.scrollY - geometry.heroTop) / (geometry.heroHeight * .82));
            scroll += (targetScroll - scroll) * ease(.09);
            const turn = smoothstep(scroll / .8);
            const scatter = smoothstep((scroll - .2) / .75) * .75 + (1 - entrance) * .8;
            const targetTiltY = !reduced && pointer.active ? (pointer.targetX / width - .5) * .26 : 0;
            const targetTiltX = !reduced && pointer.active ? (pointer.targetY / height - .5) * -.18 : 0;
            tiltY += (targetTiltY - tiltY) * ease(.045);
            tiltX += (targetTiltX - tiltX) * ease(.045);
            pointer.x += (pointer.targetX - pointer.x) * ease(.12);
            pointer.y += (pointer.targetY - pointer.y) * ease(.12);
            pointer.velocityX *= Math.exp(-.09 * delta);
            pointer.velocityY *= Math.exp(-.09 * delta);
            pointer.dx += (pointer.velocityX - pointer.dx) * ease(.08);
            pointer.dy += (pointer.velocityY - pointer.dy) * ease(.08);
            const time = reduced ? 0 : elapsed;
            const angleY = reduced ? -.12 : -.12 - turn * 1.32 + tiltY + Math.sin(time * .32) * .055;
            const angleX = reduced ? .04 : .04 + tiltX + turn * .14 + Math.sin(time * .25) * .025;
            const cosY = Math.cos(angleY), sinY = Math.sin(angleY), cosX = Math.cos(angleX), sinX = Math.sin(angleX);
            const centerX = width * (.5 - turn * .08), centerY = height * (.5 + turn * .05) + Math.sin(time * .5) * 3;
            const zoom = scale * (1 + turn * .12);
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            context.clearRect(0, 0, width, height);
            const hoverCandidates = [];
            const radius = Math.min(width * .26, 142);
            particles.forEach((particle) => {
                const x = particle.x + particle.spreadX * scatter, y = particle.y + particle.spreadY * scatter, z = particle.z + particle.spreadZ * scatter;
                const rotatedX = x * cosY + z * sinY, rotatedZ = z * cosY - x * sinY;
                const rotatedY = y * cosX - rotatedZ * sinX;
                particle.depth = y * sinX + rotatedZ * cosX;
                particle.perspective = 2.5 / (2.5 - particle.depth);
                particle.px = centerX + rotatedX * zoom * particle.perspective;
                particle.py = centerY + rotatedY * zoom * particle.perspective;
                particle.distance = Math.hypot(particle.px - pointer.x, particle.py - pointer.y);
                particle.proximity = !reduced && pointer.active ? smoothstep(1 - particle.distance / radius) : 0;
                if (particle.proximity > 0) hoverCandidates.push(particle);
            });
            // Retain a few nearby particles so the reaction follows the cursor
            // without rearranging the glyphs into a geometric ring.
            hoverCandidates.sort((a, b) => a.distance - b.distance);
            const limit = width < 420 ? 3 : 5;
            for (const particle of featured) {
                if (!pointer.active || reduced || particle.distance > radius * .9) featured.delete(particle);
            }
            for (const candidate of hoverCandidates) {
                if (featured.size >= limit || candidate.distance > radius * .85) break;
                if ([...featured].every((particle) => Math.hypot(candidate.px - particle.px, candidate.py - particle.py) > 51)) featured.add(candidate);
            }
            // Small camera sway does not need a full depth sort every animation frame.
            if (Math.abs(angleY - previousSortAngle) > .025 || scatter > .02) {
                particles.sort((a, b) => a.depth - b.depth);
                previousSortAngle = angleY;
            }
            const enlarged = [];
            particles.forEach((particle) => {
                const targetHover = featured.has(particle) ? smoothstep((radius - particle.distance) / (radius * .32)) : 0;
                particle.hover += (targetHover - particle.hover) * ease(.075);
                const angle = particle.distance > 2 ? Math.atan2(particle.py - pointer.y, particle.px - pointer.x) : particle.phase;
                const push = particle.proximity * (21 + 13 * particle.hover) + particle.hover * (18 + Math.sin(particle.phase) * 7);
                const targetX = Math.cos(angle) * push + pointer.dx * particle.hover * .35;
                const targetY = Math.sin(angle) * push + pointer.dy * particle.hover * .35;
                particle.offsetX += (targetX - particle.offsetX) * ease(.07);
                particle.offsetY += (targetY - particle.offsetY) * ease(.07);
                const wave = time * .95 + particle.phase;
                particle.px += particle.offsetX + Math.sin(wave) * particle.hover * 4;
                particle.py += particle.offsetY + Math.cos(wave) * particle.hover * 4;
                const baseSize = particle.size * clamp(width / 500, .72, 1.22) * particle.perspective;
                const largeSize = Math.min(width * .29, 124) * (.73 + .27 * random(particle.phase + 31));
                particle.drawSize = baseSize * (1 + particle.proximity * .32) + (largeSize - baseSize) * particle.hover;
                if (particle.hover > .08) enlarged.push(particle);
            });
            // Local separation prevents overlap, but each glyph remains anchored
            // to its original place in the P instead of snapping to orbit slots.
            for (let pass = 0; pass < 3; pass++) {
                enlarged.forEach((a, index) => {
                    for (let j = index + 1; j < enlarged.length; j++) {
                        const b = enlarged[j];
                        const dx = b.px - a.px, dy = b.py - a.py;
                        const distance = Math.hypot(dx, dy) || .01;
                        const minimum = (a.drawSize + b.drawSize) * .29 + 7 * Math.min(a.hover, b.hover);
                        if (distance >= minimum) continue;
                        const push = (minimum - distance) / 2;
                        const nx = distance < .1 ? Math.cos(a.phase) : dx / distance;
                        const ny = distance < .1 ? Math.sin(a.phase) : dy / distance;
                        a.px -= nx * push; a.py -= ny * push;
                        b.px += nx * push; b.py += ny * push;
                    }
                });
            }
            const drawParticle = (particle) => {
                const size = particle.drawSize;
                const rotation = particle.phase * .1 + Math.sin(time * .4 + particle.phase) * .18 + particle.hover * Math.sin(time + particle.phase) * .6;
                context.globalAlpha = clamp((dark ? .96 : .8) + particle.depth * .65 + particle.hover * .22, .45, 1) * (1 - scatter * .16);
                if (particle.hover <= .08) context.globalAlpha *= 1 - particle.proximity * .24;
                const c = Math.cos(rotation) * ratio, s = Math.sin(rotation) * ratio;
                context.setTransform(c, s, -s, c, particle.px * ratio, particle.py * ratio);
                context.drawImage(sprites[particle.kind], -size / 2, -size / 2, size, size);
            };
            particles.forEach((particle) => { if (particle.hover <= .08) drawParticle(particle); });
            enlarged.forEach(drawParticle);
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            dust.forEach((particle) => {
                const x = particle.x * width + Math.sin(time * .18 + particle.phase) * 12 + tiltY * particle.z * 45;
                const y = particle.y * height + Math.cos(time * .14 + particle.phase) * 14 - scroll * particle.z * 60;
                const size = 8 + particle.z * 13;
                context.globalAlpha = (dark ? .14 : .16) + particle.z * .12;
                context.drawImage(sprites[particle.kind], x, y, size, size);
            });
            context.globalAlpha = 1;
            scrollLift = reduced ? 0 : turn * Math.min(height * .17, 80);
            root.style.setProperty('--particle-scroll-y', `${scrollLift.toFixed(2)}px`);
        };
        const tick = (now) => {
            frame = 0;
            const delta = Math.min((now - (lastTime || now - 16.67)) / 16.67, 2);
            lastTime = now;
            if (!preference.matches) elapsed += delta / 60;
            entrance = preference.matches ? 1 : Math.min(1, entrance + delta / 95);
            render(delta);
            if (visible && !document.hidden && !preference.matches) frame = requestAnimationFrame(tick);
        };
        const start = () => {
            if (!visible || document.hidden || frame) return;
            lastTime = 0;
            frame = requestAnimationFrame(tick);
        };
        const resize = () => {
            const rect = root.getBoundingClientRect();
            const heroRect = hero.getBoundingClientRect();
            geometry = { left: rect.left, top: rect.top + window.scrollY, heroTop: heroRect.top + window.scrollY, heroHeight: Math.max(1, heroRect.height) };
            const previousWidth = width;
            width = Math.max(1, Math.round(rect.width));
            height = Math.max(1, Math.round(rect.height));
            ratio = Math.min(window.devicePixelRatio || 1, 1.75);
            canvas.width = Math.round(width * ratio);
            canvas.height = Math.round(height * ratio);
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            scale = Math.min(width * 1.01, height * 1.02);
            if (!particles.length || (previousWidth < 420) !== (width < 420)) buildParticles();
            render();
            start();
        };
        root.addEventListener('pointermove', (event) => {
            if (event.pointerType === 'touch' || preference.matches) return;
            const x = event.clientX - geometry.left, y = event.clientY - (geometry.top - window.scrollY + scrollLift);
            const eventDelta = Math.max(8, event.timeStamp - pointer.lastEvent);
            if (pointer.active) {
                pointer.velocityX = clamp((x - pointer.targetX) * 16.67 / eventDelta, -22, 22);
                pointer.velocityY = clamp((y - pointer.targetY) * 16.67 / eventDelta, -22, 22);
            }
            else { pointer.x = x; pointer.y = y; }
            pointer.lastEvent = event.timeStamp;
            pointer.targetX = x;
            pointer.targetY = y;
            pointer.active = true;
        });
        root.addEventListener('pointerleave', () => { pointer.active = false; });
        window.addEventListener('scroll', () => { pointer.active = false; }, { passive: true });
        new ResizeObserver(resize).observe(root);
        new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            if (visible) start();
            else { cancelAnimationFrame(frame); frame = 0; }
        }).observe(hero);
        new MutationObserver(() => {
            dark = document.documentElement.dataset.siteTheme === 'dark';
            paintSprites();
            start();
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-site-theme'] });
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) { cancelAnimationFrame(frame); frame = 0; }
            else start();
        });
        preference.addEventListener('change', () => {
            scroll = tiltX = tiltY = 0;
            featured.clear();
            particles.forEach((particle) => { particle.hover = particle.offsetX = particle.offsetY = 0; });
            pointer.active = false;
            start();
        });
        resize();
        root.classList.add('is-ready');
    }, { once: true });
    logo.src = canvas.dataset.logoSrc;
}
