# check_login_loader.ps1 - Verify loader styles exist in login.css
$content = Get-Content 'd:/LOTRA/public/static/css/login.css' -Raw
$patterns = @('.page-loader','.loader-logo','is-active','is-leaving','loaderPulse','contentFadeIn','fade-in-content')
foreach ($p in $patterns) {
    if ($content.Contains($p)) {
        Write-Output ($p + '  FOUND')
    } else {
        Write-Output ($p + '  MISSING')
    }
}

