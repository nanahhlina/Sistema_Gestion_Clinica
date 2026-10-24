param([switch]$ConfigureDatabase)

. "$PSScriptRoot/common.ps1"
$php = Resolve-VidaSaludPhp
$environmentFile = Join-Path $script:VidaSaludRoot '.env'
$utf8 = New-Object System.Text.UTF8Encoding($false)

function Set-VidaSaludEnvironment {
    param([string]$Name, [string]$Value)
    if ($Value -match '[\r\n]') { throw 'Los valores de entorno no pueden contener saltos de línea.' }
    $escaped = $Value.Replace('\', '\\').Replace('"', '\"').Replace('$', '\$')
    $line = $Name + '="' + $escaped + '"'
    $content = [IO.File]::ReadAllText($environmentFile)
    $pattern = '(?m)^' + [regex]::Escape($Name) + '=.*$'
    if ([regex]::IsMatch($content, $pattern)) {
        $content = [regex]::Replace($content, $pattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($match) $line })
    }
    else { $content += "`n$line`n" }
    [IO.File]::WriteAllText($environmentFile, $content, $utf8)
}

Push-Location $script:VidaSaludRoot
try {
    Write-Host "PHP seleccionado: $php"
    $newEnvironment = -not (Test-Path -LiteralPath $environmentFile)
    if ($newEnvironment) { Copy-Item -LiteralPath '.env.example' -Destination $environmentFile }
    if ($newEnvironment -or $ConfigureDatabase) {
        $databaseName = Read-Host 'Base de datos existente [Parcial2]'
        if (-not $databaseName) { $databaseName = 'Parcial2' }
        $databaseUser = Read-Host 'Usuario PostgreSQL [postgres]'
        if (-not $databaseUser) { $databaseUser = 'postgres' }
        $databasePassword = Read-Host 'Contraseña PostgreSQL' -AsSecureString
        Set-VidaSaludEnvironment 'DB_CONNECTION' 'pgsql'
        Set-VidaSaludEnvironment 'DB_DATABASE' $databaseName
        Set-VidaSaludEnvironment 'DB_USERNAME' $databaseUser
        $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($databasePassword)
        try { Set-VidaSaludEnvironment 'DB_PASSWORD' ([Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)) }
        finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
    }
    $composerCommand = Get-Command composer -ErrorAction Stop
    $composerPhar = Join-Path (Split-Path $composerCommand.Source -Parent) 'composer.phar'
    if (-not (Test-Path -LiteralPath $composerPhar)) {
        throw 'No se encontró composer.phar junto al comando composer. Instale Composer para Windows con su archivo PHAR.'
    }
    Invoke-VidaSaludPhp $php @($composerPhar, 'install', '--no-interaction', '--prefer-dist')
    Invoke-VidaSaludPhp $php @('artisan', 'config:clear', '--no-interaction')
    if ([IO.File]::ReadAllText($environmentFile) -notmatch '(?m)^APP_KEY="?base64:') {
        Invoke-VidaSaludPhp $php @('artisan', 'key:generate', '--no-interaction')
    }
    Invoke-VidaSaludPhp $php @('artisan', 'migrate', '--seed', '--no-interaction')
    Write-Host 'Instalación terminada. Ejecute .\scripts\start.ps1.'
}
finally { Pop-Location }
