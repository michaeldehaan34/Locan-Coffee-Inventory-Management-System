# Simple CSS brace-balance sanity check for LOTRA static stylesheets.
# Usage: powershell -ExecutionPolicy Bypass -File check_css_braces.ps1
$cssDir = Join-Path (Get-Location) 'public/static/css'
$files = Get-ChildItem -Path $cssDir -Filter '*.css' | Sort-Object Name
$allOk = $true
foreach ($f in $files) {
    $content = [System.IO.File]::ReadAllText($f.FullName)
    $open = 0
    $close = 0
    foreach ($ch in $content.ToCharArray()) {
        if ($ch -eq '{') { $open++ }
        elseif ($ch -eq '}') { $close++ }
    }
    $ok = ($open -eq $close)
    if (-not $ok) { $allOk = $false }
    Write-Output ("{0,-20} open={1,-5} close={2,-5} {3}" -f $f.Name, $open, $close, $(if ($ok) { 'OK' } else { 'MISMATCH' }))
}
Write-Output ''
if ($allOk) { Write-Output 'ALL CSS FILES: balanced braces' } else { Write-Output 'SOME FILES: un-balanced braces!'; exit 1 }

