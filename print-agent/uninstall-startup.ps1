$startup = [Environment]::GetFolderPath('Startup')
$linkPath = Join-Path $startup 'Mining ERP Print Agent.lnk'
if (Test-Path -LiteralPath $linkPath) { Remove-Item -LiteralPath $linkPath -Force; Write-Host 'Auto-start Mining ERP Print Agent dihapus.' } else { Write-Host 'Auto-start belum terpasang.' }
