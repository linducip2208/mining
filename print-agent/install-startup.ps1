$ErrorActionPreference = 'Stop'
$startup = [Environment]::GetFolderPath('Startup')
$linkPath = Join-Path $startup 'Mining ERP Print Agent.lnk'
$node = (Get-Command node.exe -ErrorAction Stop).Source
$agentDir = (Resolve-Path $PSScriptRoot).Path
$shell = New-Object -ComObject WScript.Shell
$shortcut = $shell.CreateShortcut($linkPath)
$shortcut.TargetPath = $node
$shortcut.Arguments = ('"{0}"' -f (Join-Path $agentDir 'server.cjs'))
$shortcut.WorkingDirectory = $agentDir
$shortcut.WindowStyle = 7
$shortcut.Description = 'Mining ERP Print Agent'
$shortcut.Save()
Write-Host "Auto-start terpasang: $linkPath"
