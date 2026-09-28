import assert from 'node:assert/strict';
import test from 'node:test';
import { initMemberNavigation } from '../../resources/js/site/member-navigation.js';

test('member tabs follow the section currently reached while scrolling', () => {
    const savedWindow = globalThis.window;
    const savedDocument = globalThis.document;
    const listeners = new Map();
    const frames = [];
    const createLink = (href, active = false) => {
        const classes = new Set(active ? ['is-active'] : []);
        const attributes = new Map();
        return {
            href,
            classList: {
                contains: (name) => classes.has(name),
                toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name),
            },
            setAttribute: (name, value) => attributes.set(name, value),
            removeAttribute: (name) => attributes.delete(name),
            getAttribute: (name) => attributes.get(name),
        };
    };
    const base = createLink('http://localhost/member/projects?site_locale=tr', true);
    const documents = createLink('http://localhost/member/projects?site_locale=tr#belgelerim');
    const results = createLink('http://localhost/member/projects?site_locale=tr#sonuclarim');
    const profile = createLink('http://localhost/member/account/edit');
    const links = [base, documents, results, profile];
    const section = (id, top) => ({
        id,
        getBoundingClientRect: () => ({ top: top - globalThis.window.scrollY, bottom: top + 180 - globalThis.window.scrollY }),
    });
    const sections = { belgelerim: section('belgelerim', 880), sonuclarim: section('sonuclarim', 1380) };

    try {
        globalThis.window = {
            location: { href: base.href, pathname: '/member/projects', hash: '' },
            scrollY: 0,
            innerHeight: 600,
            requestAnimationFrame: (callback) => { frames.push(callback); return frames.length; },
            addEventListener: (name, callback) => listeners.set(name, callback),
        };
        globalThis.document = {
            documentElement: { scrollHeight: 2100 },
            querySelector: (selector) => selector === '.site-member-nav'
                ? { querySelectorAll: () => links }
                : { getBoundingClientRect: () => ({ height: 68 }) },
            getElementById: (id) => sections[id],
        };

        initMemberNavigation();
        const scrollTo = (position) => {
            globalThis.window.scrollY = position;
            listeners.get('scroll')();
            frames.shift()();
        };

        assert.equal(base.getAttribute('aria-current'), 'page');
        scrollTo(790);
        assert.equal(documents.classList.contains('is-active'), true);
        assert.equal(documents.getAttribute('aria-current'), 'location');
        assert.equal(base.classList.contains('is-active'), false);
        scrollTo(1270);
        assert.equal(results.classList.contains('is-active'), true);
        assert.equal(documents.classList.contains('is-active'), false);
        scrollTo(0);
        assert.equal(base.classList.contains('is-active'), true);
        assert.equal(results.classList.contains('is-active'), false);

        globalThis.document.documentElement.scrollHeight = 1500;
        globalThis.window.location.hash = '#belgelerim';
        scrollTo(900);
        assert.equal(documents.classList.contains('is-active'), true);
        assert.equal(results.classList.contains('is-active'), false);

        const account = createLink('http://localhost/member/account?site_locale=tr', true);
        const information = createLink('http://localhost/member/account?site_locale=tr#bilmeniz-gerekenler');
        links.splice(0, links.length, account, information, profile);
        sections['bilmeniz-gerekenler'] = section('bilmeniz-gerekenler', 1300);
        globalThis.window.location = { href: account.href, pathname: '/member/account', hash: '#bilmeniz-gerekenler' };
        globalThis.window.scrollY = 850;
        globalThis.document.documentElement.scrollHeight = 2500;
        initMemberNavigation();
        assert.equal(information.classList.contains('is-active'), true,
            'An anchor reached from another page should activate even when it cannot cross the top threshold');
        assert.equal(account.classList.contains('is-active'), false);
        scrollTo(0);
        assert.equal(account.classList.contains('is-active'), true);
    } finally {
        globalThis.window = savedWindow;
        globalThis.document = savedDocument;
    }
});
