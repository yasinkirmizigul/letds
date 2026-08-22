[CmdletBinding()]
param(
    [ValidateRange(1024, 65535)]
    [int] $Port = 8888,

    [ValidatePattern('^[a-zA-Z0-9._-]+$')]
    [string] $Username = 'demo',

    [string] $Password,

    [switch] $SkipBuild
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Stop-ProcessTree {
    param([int] $TargetProcessId)

    $children = Get-CimInstance Win32_Process -Filter "ParentProcessId = $TargetProcessId" -ErrorAction SilentlyContinue
    foreach ($child in $children) {
        Stop-ProcessTree -TargetProcessId $child.ProcessId
    }

    Stop-Process -Id $TargetProcessId -Force -ErrorAction SilentlyContinue
}

function Test-DnsRecord {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Hostname,

        [string] $Server
    )

    $parameters = @{
        Name = $Hostname
        Type = 'A'
        DnsOnly = $true
        ErrorAction = 'Stop'
    }

    if (-not [string]::IsNullOrWhiteSpace($Server)) {
        $parameters.Server = $Server
    }

    try {
        $records = Resolve-DnsName @parameters

        return @($records | Where-Object { $_.IPAddress }).Count -gt 0
    } catch {
        return $false
    }
}

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot

if (-not (Get-Command php.exe -ErrorAction SilentlyContinue)) {
    throw 'PHP bulunamadi. PHP PATH ayarini kontrol edin.'
}

$cloudflaredCommand = Get-Command cloudflared.exe -ErrorAction SilentlyContinue
$cloudflaredCandidates = @(
    if ($cloudflaredCommand) { $cloudflaredCommand.Source }
    "${env:ProgramFiles}\cloudflared\cloudflared.exe"
    "${env:ProgramFiles(x86)}\cloudflared\cloudflared.exe"
    "${env:LOCALAPPDATA}\Microsoft\WinGet\Links\cloudflared.exe"
)
$cloudflared = $cloudflaredCandidates |
    Where-Object { $_ -and (Test-Path -LiteralPath $_) } |
    Select-Object -First 1

if (-not $cloudflared) {
    throw 'cloudflared bulunamadi. Kurulum: winget install --id Cloudflare.cloudflared --exact'
}

$occupiedPort = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if ($occupiedPort) {
    throw "$Port portu baska bir uygulama tarafindan kullaniliyor. Ornek: .\scripts\share-demo.ps1 -Port 8080"
}

if ([string]::IsNullOrWhiteSpace($Password)) {
    $Password = [guid]::NewGuid().ToString('N').Substring(0, 12)
}

if (-not $SkipBuild) {
    if (-not (Get-Command npm.cmd -ErrorAction SilentlyContinue)) {
        throw 'npm bulunamadi. Node.js PATH ayarini kontrol edin.'
    }

    Write-Host 'Frontend production paketi hazirlaniyor...' -ForegroundColor Cyan
    & npm.cmd run build
    if ($LASTEXITCODE -ne 0) {
        throw 'Frontend derlemesi basarisiz oldu.'
    }
}

$viteManifest = Join-Path $projectRoot 'public\build\manifest.json'
if (-not (Test-Path -LiteralPath $viteManifest -PathType Leaf)) {
    $buildHint = if ($SkipBuild) {
        'Komutu -SkipBuild olmadan yeniden calistirin.'
    } else {
        'Vite build cikti ayarlarini kontrol edin.'
    }

    throw "Frontend production manifesti bulunamadi: $viteManifest`n$buildHint"
}

# Laravel, public/hot varken production build yerine yerel Vite adresini kullanir.
# Quick Tunnel disaridan acildigi icin bu isaretci her paylasimda temizlenmelidir.
$viteHotFile = Join-Path $projectRoot 'public\hot'
if (Test-Path -LiteralPath $viteHotFile) {
    Remove-Item -LiteralPath $viteHotFile -Force
    Write-Host 'Eski Vite gelistirme baglantisi temizlendi; production dosyalari kullanilacak.' -ForegroundColor DarkGray
}

if (Test-Path -LiteralPath $viteHotFile) {
    throw "Vite gelistirme baglantisi temizlenemedi: $viteHotFile"
}

$env:APP_ENV = 'production'
$env:APP_DEBUG = 'false'
$env:DEMO_ACCESS_ENABLED = 'true'
$env:DEMO_ACCESS_USERNAME = $Username
$env:DEMO_ACCESS_PASSWORD = $Password
$env:QUEUE_CONNECTION = 'sync'
$env:MAIL_MAILER = 'log'
$env:LOG_LEVEL = 'warning'

& php artisan optimize:clear --no-ansi
if ($LASTEXITCODE -ne 0) {
    throw 'Laravel onbellegi temizlenemedi.'
}

$stdoutLog = Join-Path $projectRoot 'storage\logs\demo-server.out.log'
$stderrLog = Join-Path $projectRoot 'storage\logs\demo-server.err.log'
$tunnelStdoutLog = Join-Path $projectRoot 'storage\logs\demo-tunnel.out.log'
$tunnelStderrLog = Join-Path $projectRoot 'storage\logs\demo-tunnel.err.log'
$laravel = $null
$tunnel = $null

# Start-Process log redirection behavior differs between PowerShell versions.
# Removing old files prevents a previous quick-tunnel URL from being reused.
foreach ($logFile in @($stdoutLog, $stderrLog, $tunnelStdoutLog, $tunnelStderrLog)) {
    Remove-Item -LiteralPath $logFile -Force -ErrorAction SilentlyContinue
}

try {
    $laravel = Start-Process `
        -FilePath 'php.exe' `
        -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', "--port=$Port", '--no-reload') `
        -WorkingDirectory $projectRoot `
        -WindowStyle Hidden `
        -RedirectStandardOutput $stdoutLog `
        -RedirectStandardError $stderrLog `
        -PassThru

    $serverReady = $false
    for ($attempt = 0; $attempt -lt 30; $attempt++) {
        Start-Sleep -Milliseconds 500

        if ($laravel.HasExited) {
            break
        }

        try {
            $health = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/up" -UseBasicParsing -TimeoutSec 1
            if ($health.StatusCode -eq 200) {
                $serverReady = $true
                break
            }
        } catch {
            # Sunucu acilisini tamamlayana kadar tekrar dene.
        }
    }

    if (-not $serverReady) {
        $details = if (Test-Path -LiteralPath $stderrLog) {
            Get-Content -LiteralPath $stderrLog -Raw
        } else {
            'Sunucu logu olusturulamadi.'
        }

        throw "Laravel demo sunucusu baslatilamadi.`n$details"
    }

    $tunnel = Start-Process `
        -FilePath $cloudflared `
        -ArgumentList @('tunnel', '--url', "http://127.0.0.1:$Port", '--no-autoupdate') `
        -WorkingDirectory $projectRoot `
        -WindowStyle Hidden `
        -RedirectStandardOutput $tunnelStdoutLog `
        -RedirectStandardError $tunnelStderrLog `
        -PassThru

    $publicUrl = $null
    for ($attempt = 0; $attempt -lt 60; $attempt++) {
        Start-Sleep -Milliseconds 500

        if ($tunnel.HasExited) {
            break
        }

        $tunnelOutput = @(
            if (Test-Path -LiteralPath $tunnelStdoutLog) { Get-Content -LiteralPath $tunnelStdoutLog -Raw }
            if (Test-Path -LiteralPath $tunnelStderrLog) { Get-Content -LiteralPath $tunnelStderrLog -Raw }
        ) -join "`n"

        if ($tunnelOutput -match 'https://[a-z0-9-]+\.trycloudflare\.com') {
            $publicUrl = $Matches[0]
            break
        }
    }

    if (-not $publicUrl) {
        $details = @(
            if (Test-Path -LiteralPath $tunnelStdoutLog) { Get-Content -LiteralPath $tunnelStdoutLog -Raw }
            if (Test-Path -LiteralPath $tunnelStderrLog) { Get-Content -LiteralPath $tunnelStderrLog -Raw }
        ) -join "`n"

        throw "Cloudflare demo adresi olusturulamadi.`n$details"
    }

    $publicHostname = ([Uri] $publicUrl).DnsSafeHost
    $publicDnsReady = $false
    Write-Host 'Cloudflare DNS yayini dogrulaniyor...' -ForegroundColor DarkGray

    # Quick Tunnel prints its URL before the DNS record is guaranteed to exist.
    # Waiting here prevents the browser or ISP resolver from caching NXDOMAIN.
    for ($attempt = 0; $attempt -lt 90; $attempt++) {
        if ($tunnel.HasExited) {
            break
        }

        if (Test-DnsRecord -Hostname $publicHostname -Server '1.1.1.1') {
            $publicDnsReady = $true
            break
        }

        Start-Sleep -Seconds 1
    }

    if (-not $publicDnsReady) {
        $details = @(
            if (Test-Path -LiteralPath $tunnelStdoutLog) { Get-Content -LiteralPath $tunnelStdoutLog -Raw }
            if (Test-Path -LiteralPath $tunnelStderrLog) { Get-Content -LiteralPath $tunnelStderrLog -Raw }
        ) -join "`n"

        throw "Cloudflare adresi olusturuldu ancak genel DNS kaydi hazir olmadi. Lutfen tekrar deneyin.`n$details"
    }

    $systemDnsReady = Test-DnsRecord -Hostname $publicHostname
    if (-not $systemDnsReady) {
        & ipconfig.exe /flushdns | Out-Null
        Start-Sleep -Seconds 1
        $systemDnsReady = Test-DnsRecord -Hostname $publicHostname
    }

    try {
        Set-Clipboard -Value $publicUrl
        $clipboardNotice = 'Adres panoya kopyalandi.'
    } catch {
        $clipboardNotice = 'Adres panoya kopyalanamadi; terminalden kopyalayabilirsiniz.'
    }

    Write-Host ''
    Write-Host 'Demo hazir.' -ForegroundColor Green
    Write-Host "Adres          : $publicUrl" -ForegroundColor Cyan
    Write-Host "Kullanici adi : $Username" -ForegroundColor Yellow
    Write-Host "Parola         : $Password" -ForegroundColor Yellow
    Write-Host $clipboardNotice -ForegroundColor DarkGray

    if (-not $systemDnsReady) {
        Write-Host ''
        Write-Host 'Uyari: Tunel genel internette aktif, ancak Windows DNS sunucunuz yeni adresi henuz goremiyor.' -ForegroundColor Yellow
        Write-Host 'Chrome/Edge > Ayarlar > Gizlilik ve guvenlik > Guvenli DNS bolumunden Cloudflare (1.1.1.1) secin.' -ForegroundColor Yellow
        Write-Host 'Baglantiyi paylastiginiz uzak kullanicilar farkli DNS kullaniyorsa adresi acabilir.' -ForegroundColor DarkGray
    }

    Write-Host 'Demoyu kapatmak icin Ctrl+C tuslarina basin.' -ForegroundColor DarkGray
    Write-Host ''

    while (-not $tunnel.HasExited) {
        Start-Sleep -Seconds 1
    }
} finally {
    if ($tunnel -and -not $tunnel.HasExited) {
        Stop-ProcessTree -TargetProcessId $tunnel.Id
        $tunnel.WaitForExit()
    }

    if ($laravel -and -not $laravel.HasExited) {
        Stop-ProcessTree -TargetProcessId $laravel.Id
        $laravel.WaitForExit()
    }

    Write-Host 'Demo baglantisi kapatildi.' -ForegroundColor DarkGray
}
