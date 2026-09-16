$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$phpExe = Join-Path $PSScriptRoot '.runtime/php/php.exe'
if (Test-Path $phpExe) {
    & $phpExe -d "extension_dir=$PSScriptRoot/.runtime/php/ext" -d extension=pdo_sqlite -S localhost:8000 -t public
} elseif (Get-Command php -ErrorAction SilentlyContinue) {
    php -S localhost:8000 -t public
} else {
    Write-Host 'Instale PHP 8.1 ou superior com PDO SQLite e execute: php -S localhost:8000 -t public'
    exit 1
}
