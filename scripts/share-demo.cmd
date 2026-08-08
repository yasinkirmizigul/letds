@echo off
setlocal
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0share-demo.ps1" %*
exit /b %errorlevel%
