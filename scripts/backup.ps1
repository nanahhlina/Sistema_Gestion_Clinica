param(
    [Parameter(Mandatory)][string]$OutputFile,
    [string]$Database = 'Parcial2',
    [string]$PgHost = 'localhost',
    [ValidateRange(1, 65535)][int]$Port = 5432,
    [string]$Username = 'postgres'
)

. "$PSScriptRoot/common.ps1"
$pgDump = Resolve-VidaSaludPgTool 'pg_dump'
$destination = [IO.Path]::GetFullPath($OutputFile)
if (Test-Path -LiteralPath $destination) { throw 'El archivo de destino ya existe; elija un nombre nuevo.' }
if (-not (Test-Path -LiteralPath (Split-Path $destination -Parent) -PathType Container)) {
    throw 'El directorio de destino debe existir.'
}
Invoke-WithVidaSaludPgPassword {
    & $pgDump --host $PgHost --port $Port --username $Username --no-password --format=custom --no-owner --no-privileges --file $destination --dbname $Database
    if ($LASTEXITCODE -ne 0) { throw 'Falló pg_dump. El archivo parcial no debe considerarse un respaldo válido.' }
}
Write-Host "Respaldo creado: $destination"
