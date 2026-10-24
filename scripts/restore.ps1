param(
    [Parameter(Mandatory)][string]$BackupFile,
    [Parameter(Mandatory)][ValidatePattern('^[A-Za-z][A-Za-z0-9_]{0,62}$')][string]$NewDatabase,
    [string]$PgHost = 'localhost',
    [ValidateRange(1, 65535)][int]$Port = 5432,
    [string]$Username = 'postgres'
)

. "$PSScriptRoot/common.ps1"
if ($NewDatabase -ieq 'Parcial2') { throw 'Está prohibido restaurar sobre Parcial2. Indique una base nueva.' }
$source = (Resolve-Path -LiteralPath $BackupFile -ErrorAction Stop).Path
$createdb = Resolve-VidaSaludPgTool 'createdb'
$pgRestore = Resolve-VidaSaludPgTool 'pg_restore'
Invoke-WithVidaSaludPgPassword {
    & $pgRestore --list $source | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'Se requiere un respaldo válido en formato custom de pg_dump.' }
    & $createdb --host $PgHost --port $Port --username $Username --no-password --maintenance-db postgres --template template0 -- $NewDatabase
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear la base nueva. Si ya existe, elija otro nombre. No se restauró nada.' }
    & $pgRestore --host $PgHost --port $Port --username $Username --no-password --exit-on-error --single-transaction --no-owner --no-privileges --dbname $NewDatabase $source
    if ($LASTEXITCODE -ne 0) { throw 'Restauración fallida y revertida. La base nueva se conserva para diagnóstico; Parcial2 no fue modificada.' }
}
Write-Host "Respaldo restaurado en la base nueva $NewDatabase."
