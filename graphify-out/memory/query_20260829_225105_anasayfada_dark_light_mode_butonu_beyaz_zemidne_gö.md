---
type: "query"
date: "2026-08-29T22:51:05.789183+00:00"
question: "anasayfada dark light mode butonu beyaz zemidne gözükmüyor; yanına hamburger menü ve aşağı kayan, kapanabilen header navigasyonu ekle"
contributor: "graphify"
outcome: "useful"
source_nodes: ["header.blade.php", "theme-toggle.blade.php", "mobile-item.blade.php"]
---

# Q: anasayfada dark light mode butonu beyaz zemidne gözükmüyor; yanına hamburger menü ve aşağı kayan, kapanabilen header navigasyonu ekle

## Answer

Expanded from original query via graph vocab: [homepage, header, theme, switch, toggle, icon, menu, nav, navigation, mobile]. The graph identified header.blade.php, theme-toggle.blade.php, and mobile-item.blade.php as the relevant shared patterns. The homepage now uses a shared home-navigation-menu partial in both hero layouts; theme and hamburger controls have background-independent contrast, and the navigation slides down/up with accessible expanded, aria-hidden, inert, outside-click, and Escape behavior.

## Outcome

- Signal: useful

## Source Nodes

- header.blade.php
- theme-toggle.blade.php
- mobile-item.blade.php