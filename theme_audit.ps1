# LOTRA Theme Audit Script
# Scans CSS/JS/Blade files for leftover dark/coffee-theme hex codes.

$ErrorActionPreference = 'SilentlyContinue'

Write-Host "=== THEME AUDIT: leftover dark/coffee hex codes ===" -ForegroundColor Cyan
Write-Host ""

# Pattern groups: dark surfaces (0F,16,17,1A,1B,20,23,2A,2B,2C,35,3A,42,4A), coffee browns (5C,8B,2C1810,4A3728,8B5E3C,5C3A28,3B82F6), login darks
$patterns = @(
    '#0F1115', '#0F1117', '#16181D', '#171A21', '#1B1E24', '#20242B', '#23272F',
    '#2A2D35', '#353941', '#3A3E48', '#1F2026', '#2C1810', '#4A3728',
    '#5C3A28', '#8B5E3C', '#16191B', '#1E2129', '#1E2120', '#2A2E37'
)

function Scan-File($path) {
    $content = Get-Content -Path $path -Raw -ErrorAction SilentlyContinue
    if (-not $content) { return }
    foreach ($p in $patterns) {
        if ($content -match [regex]::Escape($p)) {
            $lines = Get-Content -Path $path
            for ($i = 0; $i -lt $lines.Count; $i++) {
                if ($lines[$i] -match [regex]::Escape($p)) {
                    Write-Host ("{0}:{1}: {2}" -f $path, ($i + 1), $lines[$i].Trim()) -ForegroundColor Yellow
                }
            }
        }
    }
}

$cssFiles = Get-ChildItem -Path 'public/static/css' -Filter '*.css' -Recurse
$jsFiles  = Get-ChildItem -Path 'public/static/js'  -Filter '*.js'  -Recurse
$bladeFiles = Get-ChildItem -Path 'resources/views' -Filter '*.blade.php' -Recurse

$allFiles = @($cssFiles) + @($jsFiles) + @($bladeFiles)

Write-Host "Scanning $($allFiles.Count) files for dark/coffee color remnants..." -ForegroundColor Gray
Write-Host ""

foreach ($f in $allFiles) {
    Scan-File $f.FullName
}

Write-Host ""
Write-Host "=== Audit complete ===" -ForegroundColor Green

