---
type: "query"
date: "2026-10-03T22:04:42.148391+00:00"
question: "Apply the InfinityFree pinned CDN workaround and verify the regenerated deployment package"
contributor: "graphify"
outcome: "useful"
source_nodes: ["vite"]
---

# Q: Apply the InfinityFree pinned CDN workaround and verify the regenerated deployment package

## Answer

Expanded from graph vocabulary: vite integrity. Both Blade tags now use the exact pinned jsDelivr URL and matching SHA384 with crossorigin anonymous and no async or defer. The CDN response was HTTP200 and matched SRI. All source bundle copies retained. Ten oversized files excluded only from deployment after static reference review. Final CDN snapshot: 15076 files, 146824725 bytes, zero host size violations, 78 runtime packages and 24 Vite manifest files preserved. Largest JS744515 bytes; eight pre-existing missing visual images unchanged. Business logic, Vite, dependency versions, database config and public htaccess untouched.

## Outcome

- Signal: useful

## Source Nodes

- vite