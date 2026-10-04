// Read-only audit: no database/network access and no application asset writes.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';
import { minify } from 'rolldown/experimental';

const sourceRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const args = process.argv.slice(2);
if (args.length && (args.length !== 2 || args[0] !== '--deployment')) {
    throw new Error('Usage: node scripts/audit-infinityfree-assets.mjs [--deployment PATH_TO_HTDOCS]');
}
const deployment = args.length === 2;
const root = deployment ? path.resolve(args[1]) : sourceRoot;
const cdnUrl = 'https://cdn.jsdelivr.net/gh/yasinkirmizigul/letds@78ed8455ae58351e82cc6471afc8181785870eb3/public/assets/admin/plugins/global/plugins.bundle.js';
const cdnSri = 'sha384-0XhE5yWVZ06NM+Ne65TKqFljEnvP0CxyX+wM1nrNw/94047GRXFhy1QK9ruhjw5M';
const relative = (file) => path.relative(root, file).replaceAll('\\', '/');
function files(dir) {
    if (!fs.existsSync(dir)) return [];
    return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
        const file = path.join(dir, entry.name);
        if (fs.lstatSync(file).isSymbolicLink()) return [];
        return entry.isDirectory() ? files(file) : entry.isFile() ? [file] : [];
    });
}
const sources = ['app', 'resources', 'routes', 'config', 'bootstrap'].flatMap((dir) => files(path.join(root, dir)))
    .filter((file) => /\.(?:php|js|css)$/.test(file));
const sourceText = sources.map((file) => ({ file: relative(file), text: fs.readFileSync(file, 'utf8') }));
const oversized = [];
for (const file of files(path.join(root, 'public'))) {
    const bytes = fs.statSync(file).size;
    if (!file.endsWith('.js') || bytes < 1_000_000) continue;
    const publicPath = relative(file).replace(/^public\//, '');
    const usedBy = sourceText.filter((source) => source.text.replace(/https?:\/\/[^\s"'<>]+/g, '').includes(publicPath)).map((source) => source.file);
    const result = await minify(relative(file), fs.readFileSync(file, 'utf8'), {
        module: false, compress: false, mangle: false,
        codegen: { legalComments: 'inline' },
    });
    oversized.push({ file: relative(file), bytes, usedBy,
        whitespaceOnlyBytes: result.errors.length ? null : Buffer.byteLength(result.code),
        parseErrors: result.errors.map((error) => error.message),
    });
}
const manifest = JSON.parse(fs.readFileSync(path.join(root, 'public/build/manifest.json'), 'utf8'));
const manifestFiles = [...new Set(Object.values(manifest).flatMap((entry) => [entry.file, ...(entry.css ?? []), ...(entry.assets ?? [])]).filter(Boolean))];
const missingBuildFiles = manifestFiles.filter((file) => !fs.existsSync(path.join(root, 'public/build', file)));
const unknownImports = Object.values(manifest).flatMap((entry) => [...(entry.imports ?? []), ...(entry.dynamicImports ?? [])]).filter((key) => !manifest[key]);
const literalAssets = new Set();
for (const source of sourceText) {
    for (const match of source.text.matchAll(/\basset\(\s*(['"])([^'"]+)\1\s*\)/g)) {
        if (!/^(https?:)?\/\//.test(match[2])) literalAssets.add(match[2].replace(/^\//, '').split('?')[0]);
    }
}
const missingLiteralAssets = [...literalAssets].filter((file) => !fs.existsSync(path.join(root, 'public', file)));
const forbiddenAssetPrefixes = [...literalAssets].filter((file) => /^(?:public|vendor|app|bootstrap|config|database|resources|routes|tests)\//.test(file));
const cdnReferences = ['resources/views/admin/layouts/partials/scripts.blade.php', 'resources/views/site/appointments/index.blade.php'].map((file) => {
    const text = fs.readFileSync(path.join(root, file), 'utf8');
    const tags = [...text.matchAll(/<script\b[\s\S]*?<\/script>/g)].map((match) => match[0]);
    const matches = tags.filter((tag) => /\bsrc\s*=\s*["'][^"']*plugins\.bundle\.js["']/.test(tag));
    const tag = matches[0] ?? '';
    const attribute = (name) => tag.match(new RegExp(`\\b${name}\\s*=\\s*(["'])(.*?)\\1`))?.[2];
    return { file, exactlyOneReference: matches.length === 1, pinnedUrl: attribute('src') === cdnUrl,
        expectedSri: attribute('integrity') === cdnSri, anonymousCors: attribute('crossorigin') === 'anonymous',
        noAsyncOrDefer: !/\s(?:async|defer)(?:\s|=|>)/i.test(tag),
        noLocalBundleReference: !/\basset\([^)]*plugins\.bundle\.js/.test(text) };
});
const hash = (file) => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const changedBuildFiles = deployment ? files(path.join(sourceRoot, 'public/build')).filter((file) => {
    const target = path.join(root, path.relative(sourceRoot, file));
    return !fs.existsSync(target) || hash(file) !== hash(target);
}).map((file) => path.relative(sourceRoot, file).replaceAll('\\', '/')) : [];
// Existing visual 404s are reported separately; packaging must introduce none.
const newlyMissingAssets = deployment ? missingLiteralAssets.filter((file) => fs.existsSync(path.join(sourceRoot, 'public', file))) : [];
const publicFiles = files(path.join(root, 'public'));
const largestJavaScript = publicFiles.filter((file) => file.endsWith('.js'))
    .map((file) => ({ file: relative(file), bytes: fs.statSync(file).size }))
    .sort((a, b) => b.bytes - a.bytes).slice(0, 5);
const allFiles = deployment ? files(root) : [];
const overHostingLimits = deployment ? allFiles.filter((file) => {
    const size = fs.statSync(file).size;
    return (/\.(?:php|html?|js)$/i.test(file) && size >= 1_000_000)
        || (path.basename(file) === '.htaccess' && size >= 10_000) || size >= 10_000_000;
}).map((file) => ({ file: relative(file), bytes: fs.statSync(file).size })) : [];
const excludedBundlesAbsent = ['public/assets/admin/plugins/global/plugins.bundle.js', 'public/assets/site/plugins/global/plugins.bundle.js']
    .every((file) => !fs.existsSync(path.join(root, file)));
console.log(JSON.stringify({ scope: deployment ? 'deployment' : 'source', root, oversized,
    vite: { entries: Object.keys(manifest).length, referencedFiles: manifestFiles.length, missingBuildFiles, unknownImports },
    urlSources: sourceText.filter((source) => /\b(?:asset|url|route)\(|@vite|Storage::.*url\(|->url\(/.test(source.text)).length,
    literalAssetCount: literalAssets.size, missingLiteralAssets, forbiddenAssetPrefixes,
    publicHotExists: fs.existsSync(path.join(root, 'public/hot')),
    cdnReferences, changedBuildFiles, newlyMissingAssets, largestJavaScript,
    ...(deployment ? { excludedBundlesAbsent, overHostingLimits, fileCount: allFiles.length,
        totalBytes: allFiles.reduce((total, file) => total + fs.statSync(file).size, 0) } : {}),
}, null, 2));
if (missingBuildFiles.length || unknownImports.length || forbiddenAssetPrefixes.length
    || changedBuildFiles.length || newlyMissingAssets.length || fs.existsSync(path.join(root, 'public/hot'))
    || cdnReferences.some((ref) => Object.entries(ref).some(([key, value]) => key !== 'file' && !value))
    || (deployment && (!excludedBundlesAbsent || overHostingLimits.length))) process.exitCode = 1;
