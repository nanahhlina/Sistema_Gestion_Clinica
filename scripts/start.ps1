param(
    [ValidateRange(1, 65535)][int]$Port = 8000
)

. "$PSScriptRoot/common.ps1"
$php = Resolve-VidaSaludPhp
Push-Location $script:VidaSaludRoot
try {
    Write-Host "PHP seleccionado: $php"
    Invoke-VidaSaludPhp $php @('artisan', 'serve', '--host=127.0.0.1', "--port=$Port", '--no-interaction')
}
finally { Pop-Location }
