# verify_tokens2.ps1 - Confirm required design tokens are defined in theme.css
$theme = Get-Content 'd:/LOTRA/public/static/css/theme.css' -Raw
$required = @('--focus-ring','--focus-ring-brand','--ring-width','--glow-primary','--glow-success','--glow-danger','--glow-warning','--btn-active-translate','--transition','--transition-fast','--shadow-xs','--shadow-soft','--shadow-hover')
foreach ($tok in $required) {
    if ($theme.Contains($tok)) {
        Write-Output ($tok + '  FOUND')
    } else {
        Write-Output ($tok + '  MISSING')
    }
}

