# Requires: Node.js, lighthouse installed globally (npm install -g lighthouse)
param(
    [string]$Url = 'https://kuretemizlik.local/app',
    [ValidateSet('desktop','mobile')]
    [string]$Preset = 'desktop',
    [string]$OutputPrefix = 'lighthouse-report'
)

$ErrorActionPreference = 'Stop'

if (-not (Get-Command lighthouse -ErrorAction SilentlyContinue)) {
    Write-Host 'Installing Lighthouse CLI globally...'
    npm install -g lighthouse | Out-Null
}

$env:LIGHTHOUSE_CLI_NO_ERROR_REPORTING = '1'
$chromeFlags = '--headless --ignore-certificate-errors --disable-dev-shm-usage --no-sandbox'

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$outputBase = "${OutputPrefix}-${Preset}-${timestamp}"

$lighthouseArgs = @(
    $Url,
    "--preset=$Preset",
    "--chrome-flags=$chromeFlags",
    '--output=json',
    '--output=html',
    "--output-path=$outputBase",
    '--enable-error-reporting=false',
    '--quiet'
)

Write-Host "Running Lighthouse ($Preset) for $Url..."

try {
    lighthouse @lighthouseArgs
    Write-Host "Reports generated: ${outputBase}.report.json / .report.html"
} catch {
    Write-Error "Lighthouse run failed: $_"
    exit 1
} finally {
    Remove-Item Env:LIGHTHOUSE_CLI_NO_ERROR_REPORTING -ErrorAction SilentlyContinue
}
