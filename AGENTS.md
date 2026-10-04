## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, use the installed graphify skill or instructions before doing anything else.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- Dirty graphify-out/ files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify. Only skip graphify if the task is about stale or incorrect graph output, or the user explicitly says not to use it.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

## InfinityFree deployment mirror

- The primary project is the development source; `.deploy/htdocs/` is the stable, downloaded InfinityFree upload mirror. Do not create dated deployment directories.
- After implementing and verifying requested changes, copy only corresponding deployable files into the mirror. For CSS/JS build changes, compile locally and also synchronize `public/build/` and its manifest.
- Use `scripts/prepare-infinityfree-upload.ps1 -Paths <changed-project-relative-paths>`; use `-Preview` first. `vendor` requires explicit `-IncludeVendor` and a reviewed local production Composer install.
- Never read/print credential contents or overwrite the mirror's real `.env`. Preserve `storage/`, user uploads, runtime caches, and unknown remote-only files. Never run the downloaded application or connect to its database.
- Preserve pinned jsDelivr/SRI references. Do not copy either oversized `plugins.bundle.js`, `public/hot`, development dependencies, or the local storage junction into the mirror.
- Do not delete/rebuild the downloaded mirror from scratch. Do not FTP upload automatically, commit, or push.
- End each change with exact remote-relative upload files/folders, or explicitly state no upload is needed. Upload build assets before `public/build/manifest.json`.
