# Build a cPanel upload zip on Windows (PowerShell).
# Run from project root:  powershell -ExecutionPolicy Bypass -File deploy/cpanel/build-package.ps1

$ErrorActionPreference = "Stop"
$Root = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
Set-Location $Root

$DistDir = Join-Path $Root "deploy\cpanel\dist"
$StageDir = Join-Path $DistDir "saas-leadflow"
$ZipPath = Join-Path $DistDir "saas-leadflow-cpanel.zip"

Write-Host "==> Installing PHP dependencies (no-dev)..."
composer install --no-dev --optimize-autoloader --no-interaction

Write-Host "==> Installing Node dependencies..."
npm ci --ignore-scripts

Write-Host "==> Building frontend assets..."
npm run build

if (Test-Path $StageDir) { Remove-Item -Recurse -Force $StageDir }
if (Test-Path $ZipPath) { Remove-Item -Force $ZipPath }
New-Item -ItemType Directory -Force -Path $StageDir | Out-Null

$ExcludeDirs = @(
    ".git", ".github", ".cursor", ".codex", ".idea", ".vscode", ".zed", ".nova",
    "node_modules", "tests", "deploy", "docker", "storage\logs", "storage\pail",
    "storage\framework\cache", "storage\framework\sessions", "storage\framework\views",
    "storage\app\private", "storage\app\public"
)

Write-Host "==> Staging files for upload..."
Get-ChildItem -Force $Root | ForEach-Object {
    $name = $_.Name
    if ($name -in @(".env", ".env.docker", ".env.backup", ".env.production", ".env.cpanel")) { return }
    if ($name -in @("node_modules", ".git", "deploy", "docker", "tests")) { return }
    Copy-Item -Path $_.FullName -Destination (Join-Path $StageDir $name) -Recurse -Force
}

# Ensure required empty storage dirs exist
@(
    "storage\app\public",
    "storage\app\private",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs",
    "bootstrap\cache"
) | ForEach-Object {
    $path = Join-Path $StageDir $_
    New-Item -ItemType Directory -Force -Path $path | Out-Null
}

# Keep import templates if present
$templatesSrc = Join-Path $Root "storage\app\templates"
if (Test-Path $templatesSrc) {
    Copy-Item -Path $templatesSrc -Destination (Join-Path $StageDir "storage\app\templates") -Recurse -Force
}

# Do not ship local env / hot file
@(
    (Join-Path $StageDir ".env"),
    (Join-Path $StageDir "public\hot"),
    (Join-Path $StageDir "public\storage")
) | ForEach-Object { if (Test-Path $_) { Remove-Item -Recurse -Force $_ } }

Copy-Item (Join-Path $Root ".env.cpanel.example") (Join-Path $StageDir ".env.cpanel.example") -Force
Copy-Item (Join-Path $Root ".htaccess") (Join-Path $StageDir ".htaccess") -Force

Write-Host "==> Creating zip: $ZipPath"
Compress-Archive -Path (Join-Path $StageDir "*") -DestinationPath $ZipPath -Force

Write-Host ""
Write-Host "Done."
Write-Host "Upload this file to cPanel File Manager: $ZipPath"
Write-Host "Then follow: deploy/cpanel/README.md"
