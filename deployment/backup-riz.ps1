param(
    [string]$BackupDirectory = $(if ($env:RIZ_BACKUP_DIR) { $env:RIZ_BACKUP_DIR } else { 'C:\RizCateringBackups' }),
    [int]$RetentionDays = 30,
    [string]$MySqlDump = 'C:\xampp\mysql\bin\mysqldump.exe',
    [string]$DefaultsFile = $env:RIZ_MYSQL_DEFAULTS_FILE
)

$ErrorActionPreference = 'Stop'
if ([string]::IsNullOrWhiteSpace($BackupDirectory)) { throw 'Set RIZ_BACKUP_DIR to a restricted directory outside the website document root.' }
if ([string]::IsNullOrWhiteSpace($DefaultsFile) -or -not (Test-Path -LiteralPath $DefaultsFile -PathType Leaf)) { throw 'Set RIZ_MYSQL_DEFAULTS_FILE to a private MySQL option file readable only by the scheduled-task account.' }
if (-not (Test-Path -LiteralPath $MySqlDump -PathType Leaf)) { throw "mysqldump not found: $MySqlDump" }
if ($RetentionDays -lt 1) { throw 'RetentionDays must be at least 1.' }

$resolvedBackupDir = [IO.Path]::GetFullPath($BackupDirectory)
New-Item -ItemType Directory -Force -Path $resolvedBackupDir | Out-Null
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$sqlPath = Join-Path $resolvedBackupDir "riz_catering-$stamp.sql"
$zipPath = "$sqlPath.zip"
$arguments = @('--defaults-extra-file=' + $DefaultsFile, '--single-transaction', '--routines', '--triggers', '--events', '--databases', 'riz_catering', '--result-file=' + $sqlPath)
& $MySqlDump @arguments
if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $sqlPath -PathType Leaf)) { Remove-Item -LiteralPath $sqlPath -Force -ErrorAction SilentlyContinue; throw "mysqldump failed with exit code $LASTEXITCODE" }
Compress-Archive -LiteralPath $sqlPath -DestinationPath $zipPath -CompressionLevel Optimal
Remove-Item -LiteralPath $sqlPath -Force
Get-ChildItem -LiteralPath $resolvedBackupDir -File -Filter 'riz_catering-*.sql.zip' |
    Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$RetentionDays) } |
    Remove-Item -Force
Write-Output "Database backup created: $zipPath"
