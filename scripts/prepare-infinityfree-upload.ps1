# Selected-file sync into the downloaded .deploy/htdocs mirror; never FTP or DB.
[CmdletBinding()]
param([string[]]$Paths = @(), [switch]$Preview, [switch]$IncludeVendor)
$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$htdocs = [IO.Path]::GetFullPath((Join-Path $projectRoot '.deploy/htdocs'))
if (-not (Test-Path -LiteralPath $htdocs -PathType Container)) { throw 'Download remote htdocs into .deploy/htdocs first. Never recreate live files from a blank template.' }
if (-not $Paths.Count) { throw 'Specify changed project-relative -Paths explicitly. Use -Preview to review first.' }
$excludedPublicAssets = @(
    'public/assets/admin/plugins/global/plugins.bundle.js',
    'public/assets/site/plugins/global/plugins.bundle.js',
    'public/assets/admin/js/datatables/allowed-ip-addresses.js',
    'public/assets/admin/plugins/custom/ckeditor/ckeditor-balloon-block.bundle.js',
    'public/assets/admin/plugins/custom/ckeditor/ckeditor-balloon.bundle.js',
    'public/assets/admin/plugins/custom/ckeditor/ckeditor-classic.bundle.js',
    'public/assets/admin/plugins/custom/ckeditor/ckeditor-document.bundle.js',
    'public/assets/admin/plugins/custom/ckeditor/ckeditor-inline.bundle.js',
    'public/assets/admin/plugins/custom/datatables/datatables.bundle.js',
    'public/assets/admin/plugins/custom/tinymce/tinymce.bundle.js'
)
$allowedRoots = @('app','bootstrap','config','database','public','resources','routes','lang','vendor')
$allowedFiles = @('artisan','composer.json','composer.lock','.htaccess')
$plan = [Collections.Generic.List[object]]::new()
$seen = [Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)

function Assert-NoLink([string]$Path) {
    $cursor = $Path
    while ($cursor.StartsWith($projectRoot + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
        if ((Test-Path -LiteralPath $cursor) -and ((Get-Item -LiteralPath $cursor -Force).Attributes -band [IO.FileAttributes]::ReparsePoint)) { throw "Refusing symbolic link/junction: $cursor" }
        $cursor = Split-Path -Parent $cursor
    }
}
function Is-Protected([string]$Relative) {
    if ($Relative.Split('/') | Where-Object { $_ -match '^\.env(?:\.|$)' -or $_ -in @('auth.json','.git','.github','.svn','.hg','.codex','.agents','node_modules') }) { return $true }
    if ($Relative -match '^(?:storage|bootstrap/cache|public/storage|public/uploads)(?:/|$)') { return $true }
    if ($Relative -eq 'public/hot' -or $Relative -in $excludedPublicAssets) { return $true }
    if ($Relative -match '\.(?:map|log|exe|apk)$' -or $Relative -match '^database/.*\.(?:sql|sqlite|sqlite3|db)(?:-(?:wal|shm))?$') { return $true }
    return $false
}
function Plan-File([IO.FileSystemInfo]$Item) {
    $relative = $Item.FullName.Substring($projectRoot.Length + 1).Replace('\','/')
    if (Is-Protected $relative) { return }
    Assert-NoLink $Item.FullName
    if ($Item.PSIsContainer) {
        foreach ($child in Get-ChildItem -LiteralPath $Item.FullName -Force) { Plan-File $child }
        return
    }
    if (-not $seen.Add($relative)) { return }
    $target = [IO.Path]::GetFullPath((Join-Path $htdocs $relative))
    if (-not $target.StartsWith($htdocs + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) { throw 'Target escapes htdocs.' }
    Assert-NoLink $target
    if (($Item.Extension -in @('.php','.html','.htm','.js') -and $Item.Length -ge 1000000) -or ($Item.Name -eq '.htaccess' -and $Item.Length -ge 10000) -or $Item.Length -ge 10000000) { throw "File exceeds InfinityFree size limit: $relative" }
    if ((Test-Path -LiteralPath $target -PathType Leaf) -and (Get-FileHash -LiteralPath $Item.FullName).Hash -eq (Get-FileHash -LiteralPath $target).Hash) { return }
    $plan.Add([pscustomobject]@{ Relative=$relative; Source=$Item.FullName; Target=$target })
}
foreach ($relativePath in $Paths) {
    $relative = $relativePath.Replace('\','/').TrimEnd('/')
    if ([IO.Path]::IsPathRooted($relative) -or $relative.Split('/') -contains '..' -or $relative -eq '.' -or -not $relative) { throw "Use project-relative paths: $relativePath" }
    if (Is-Protected $relative) { throw "Protected deployment path: $relative" }
    $first = $relative.Split('/')[0]
    if ($first -notin $allowedRoots -and $relative -notin $allowedFiles) { throw "Not a deployable project path: $relative" }
    if ($first -eq 'vendor' -and -not $IncludeVendor) { throw 'vendor requires -IncludeVendor after a reviewed local Composer production install.' }
    $source = [IO.Path]::GetFullPath((Join-Path $projectRoot $relative))
    if (-not $source.StartsWith($projectRoot + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) { throw 'Source escapes project.' }
    Assert-NoLink $source
    Plan-File (Get-Item -LiteralPath $source -Force)
}
# Publish new assets before the manifest. Keep remote-only/old files intact.
$ordered = @($plan | Sort-Object @{Expression={ if ($_.Relative -eq 'public/build/manifest.json') { 1 } else { 0 } }},Relative)
foreach ($entry in $ordered) {
    if (-not $Preview) {
        [void](New-Item -ItemType Directory -Path (Split-Path -Parent $entry.Target) -Force)
        Copy-Item -LiteralPath $entry.Source -Destination $entry.Target -Force
    }
    Write-Output $entry.Relative
}
Write-Host "$(if ($Preview) { 'Preview' } else { 'Updated' }): $($ordered.Count) file(s) in $htdocs"
Write-Host 'Existing .env, storage, uploads, caches and extra remote files preserved; no files deleted.'
