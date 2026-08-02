# verify_tokens.ps1 - Verify design token definitions across CSS files
$files = Get-ChildItem -Path 'd:/LOTRA/public/static/css/*.css'
$patterns = '--focus-ring','--glow-primary','--glow-success','--glow-danger','--glow-warning','--btn-active-translate'
foreach ($f in $files) {
    $content = Get-Content $f.FullName -Raw
    foreach ($p in $patterns) {
        if ($content -match [regex]::Escape($p)) {
            # Show the defining line (with colon, i.e. var definition) vs usage
            $lines = $content -split "`n"
            foreach ($line in $lines) {
                if ($line -match [regex]::Escape($p)) {
                    $type = if ($line -match ':\s*[0-9]|:\s*var|:\s*rgb|:\s*#|:\s*rgba') { 'DEF ' } else { 'USE ' }
                    Write-Output ($f.Name + '  ' + $type + '  ' + $p + '  ->  ' + $line.Trim())
                }
            }
        }
    }
}

--ring-width
--transition
--transition-fast
--shadow-xs
--shadow-soft
--shadow-hover
