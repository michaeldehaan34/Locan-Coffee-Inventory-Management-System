# Scan Blade templates for component classes actually used, so CSS work maps to real markup.
$root = Join-Path (Get-Location) 'resources/views'
$files = Get-ChildItem -Path $root -Recurse -Filter '*.blade.php'

$patterns = @{
    'btn variants'   = 'btn-outline-(primary|success|warning|info|danger)|btn-success|btn-danger|btn-info|btn-warning|btn-primary|btn-secondary|btn-light|btn-outline-light|btn-outline-secondary'
    'form controls'  = 'form-check|form-check-input|form-check-label|textarea|form-range|form-floating|input-group|form-select'
    'table-ish'      = 'table_borderless|table-sm|card-footer|nav-tabs|nav-link|dropdown|btn-sm|btn-lg|badge'
}

foreach ($key in $patterns.Keys) {
    Write-Output ("===== " + $key + " =====")
    $results = $files | Select-String -Pattern $patterns[$key]
    $lines = $results | ForEach-Object { $_.Line.Trim() } | Sort-Object -Unique
    $lines | ForEach-Object { Write-Output $_ }
    Write-Output ("(match count: " + $lines.Count + ")`n")
}

