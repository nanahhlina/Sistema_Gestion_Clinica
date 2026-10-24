Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$script:VidaSaludRoot = Split-Path $PSScriptRoot -Parent

function Test-VidaSaludPhp {
    param([Parameter(Mandatory)][string]$Path)
    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) { return $false }
    & $Path -r 'exit(PHP_VERSION_ID >= 80300 && extension_loaded(''pdo_pgsql'') ? 0 : 1);' 2>$null | Out-Null
    return $LASTEXITCODE -eq 0
}

function Resolve-VidaSaludPhp {
    if ($env:VIDA_SALUD_PHP) {
        if (Test-VidaSaludPhp $env:VIDA_SALUD_PHP) { return $env:VIDA_SALUD_PHP }
        throw 'VIDA_SALUD_PHP debe apuntar a PHP >= 8.3 con pdo_pgsql habilitado.'
    }
    $command = Get-Command php -ErrorAction SilentlyContinue
    if ($command -and (Test-VidaSaludPhp $command.Source)) { return $command.Source }
    $fallback = Join-Path $env:USERPROFILE '.config/vida-salud/php85/php.exe'
    if (Test-VidaSaludPhp $fallback) { return $fallback }
    throw 'No se encontró PHP >= 8.3 con pdo_pgsql. Configure VIDA_SALUD_PHP con la ruta completa a php.exe.'
}

function Invoke-VidaSaludPhp {
    param([Parameter(Mandatory)][string]$Php, [Parameter(Mandatory)][string[]]$Arguments)
    & $Php @Arguments
    if ($LASTEXITCODE -ne 0) { throw "PHP terminó con código $LASTEXITCODE." }
}

function Resolve-VidaSaludPgTool {
    param([Parameter(Mandatory)][string]$Name)
    $command = Get-Command $Name -ErrorAction SilentlyContinue
    if ($command) { return $command.Source }
    $postgresRoot = Join-Path $env:ProgramFiles 'PostgreSQL'
    if (Test-Path -LiteralPath $postgresRoot) {
        $versions = Get-ChildItem -LiteralPath $postgresRoot -Directory |
            Where-Object { $_.Name -match '^\d+(\.\d+)*$' } |
            Sort-Object { if ($_.Name.Contains('.')) { [version]$_.Name } else { [version]($_.Name + '.0') } } -Descending
        foreach ($version in $versions) {
            $candidate = Join-Path $version.FullName "bin/$Name.exe"
            if (Test-Path -LiteralPath $candidate -PathType Leaf) { return $candidate }
        }
    }
    throw "No se encontró $Name. Agregue el directorio bin de PostgreSQL al PATH."
}

function Invoke-WithVidaSaludPgPassword {
    param([Parameter(Mandatory)][scriptblock]$Action)
    $previousPassword = $env:PGPASSWORD
    try {
        if (-not $env:PGPASSWORD -and -not $env:PGPASSFILE) {
            $securePassword = Read-Host 'Contraseña de PostgreSQL' -AsSecureString
            $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
            try { $env:PGPASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) }
            finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
        }
        & $Action
    }
    finally { $env:PGPASSWORD = $previousPassword }
}
