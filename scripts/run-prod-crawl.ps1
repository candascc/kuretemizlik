# Production Full Crawl Execution Script
# 
# ROUND 27: Crawl Hardening & Exit Code Normalization
# 
# ROUND 26: Deep Crawl Execution - PowerShell wrapper for recursive crawl
# 
# This script runs the full recursive crawl with proper environment variables.
# Local QA only - NOT for production deployment.
#
# Usage:
#   .\scripts\run-prod-crawl.ps1
#   .\scripts\run-prod-crawl.ps1 -BaseUrl "https://www.kuretemizlik.com/app" -MaxDepth 2 -MaxPages 50

param(
    [string]$BaseUrl = "https://www.kuretemizlik.com/app",
    [string]$StartPath = "/",
    [int]$MaxDepth = 3,
    [int]$MaxPages = 150,
    [string]$Roles = "admin"   # Virgülle ayrılmış liste: "admin", "admin,ops,mgmt" gibi
)

# Set environment variables for current process
$env:PROD_BASE_URL = $BaseUrl
$env:START_PATH = $StartPath
$env:MAX_DEPTH = "$MaxDepth"
$env:MAX_PAGES = "$MaxPages"

# Not: Admin login için gerekli env var'lar (.env / sistem ortamı) üzerinden okunuyor.
# Burada yeni bir secret hard-code edilmiyor.
# Script içinde PROD_ADMIN_EMAIL, PROD_ADMIN_PASSWORD veya ADMIN_EMAIL, ADMIN_PASSWORD
# environment variable'ları varsa onlar kullanılacak.

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ROUND 28: Role-Aware Crawl Execution" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[RUN] Parameters:" -ForegroundColor Yellow
Write-Host "  PROD_BASE_URL = $BaseUrl"
Write-Host "  START_PATH    = $StartPath"
Write-Host "  MAX_DEPTH     = $MaxDepth"
Write-Host "  MAX_PAGES     = $MaxPages"
Write-Host "  ROLES         = $Roles"
Write-Host ""

# Parse roles
$roleList = $Roles.Split(",") | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne "" }

if ($roleList.Count -eq 0) {
    Write-Host "[ERROR] No valid roles specified. Use -Roles 'admin' or -Roles 'admin,ops,mgmt'" -ForegroundColor Red
    exit 1
}

# Set common environment variables
$env:PROD_BASE_URL = $BaseUrl
$env:START_PATH = $StartPath
$env:MAX_DEPTH = "$MaxDepth"
$env:MAX_PAGES = "$MaxPages"

# Run crawl for each role
foreach ($role in $roleList) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "ROUND 28: Role Crawl - $role" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""

    $env:CRAWL_ROLE_KEY = $role

    Write-Host "[RUN] Executing for role '$role': npm run check:prod:browser:crawl:roles" -ForegroundColor Yellow
    Write-Host ""

    npm run check:prod:browser:crawl:roles

    if ($LASTEXITCODE -ne 0) {
        Write-Host ""
        Write-Host "[WARN] Crawl for role '$role' finished with exit code $LASTEXITCODE (ignored, reports still useful)" -ForegroundColor Yellow
        # Exit code'leri normalize etmek istersen, burada sadece logla, script'i kesme
    } else {
        Write-Host ""
        Write-Host "[OK] Crawl for role '$role' completed." -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "[OK] All role crawls completed." -ForegroundColor Green
Write-Host ""
Write-Host "Generated reports (per role):" -ForegroundColor Cyan
foreach ($role in $roleList) {
    $roleUpper = $role.ToUpper()
    Write-Host "  - PRODUCTION_BROWSER_CHECK_CRAWL_$roleUpper.json"
    Write-Host "  - PRODUCTION_BROWSER_CHECK_CRAWL_$roleUpper.md"
}
Write-Host ""


