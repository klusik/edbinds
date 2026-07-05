@echo off
setlocal EnableExtensions

REM ===========================================================
REM Elite Bindings Vault deployment package
REM
REM Creates:
REM   deploy\deploy.zip
REM
REM Excludes runtime/user/generated files:
REM   config\config.php
REM   config\*.local.php
REM   storage\uploads\
REM   storage\cache\
REM   data\
REM   deploy\
REM   .git\
REM   .github\
REM   IDE/OS garbage
REM
REM Requirements:
REM   Windows 10/11 with PowerShell 5+
REM ===========================================================

set "PROJECT_ROOT=%~dp0"
cd /d "%PROJECT_ROOT%"

if not exist "deploy" (
    mkdir "deploy"
)

if exist "deploy\deploy.zip" (
    del /q "deploy\deploy.zip"
)

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
"$ErrorActionPreference = 'Stop';" ^
"$root = (Get-Location).Path;" ^
"$destination = Join-Path $root 'deploy\deploy.zip';" ^
"$excludedDirectories = @('.git', '.github', '.idea', '.vscode', 'deploy', 'storage\uploads', 'storage\cache', 'data');" ^
"$excludedFiles = @('config\config.php', 'Thumbs.db', '.DS_Store');" ^
"$excludedExtensions = @('.log', '.zip', '.sql.gz', '.sql.zip', '.dump');" ^
"$files = Get-ChildItem -Path $root -Recurse -File | Where-Object {" ^
"    $relative = $_.FullName.Substring($root.Length + 1).Replace('/', '\');" ^
"    $parts = $relative -split '\\';" ^
"    $isExcludedDir = $false;" ^
"    foreach ($dir in $excludedDirectories) {" ^
"        $dirParts = $dir -split '\\';" ^
"        if ($dirParts.Count -eq 1 -and ($parts -contains $dir)) { $isExcludedDir = $true; break; }" ^
"        if ($dirParts.Count -gt 1 -and $relative.StartsWith($dir + '\')) { $isExcludedDir = $true; break; }" ^
"    }" ^
"    $isLocalConfig = $relative -like 'config\*.local.php';" ^
"    $isExcludedFile = $excludedFiles -contains $relative;" ^
"    $isExcludedExtension = $false;" ^
"    foreach ($ext in $excludedExtensions) { if ($relative.ToLowerInvariant().EndsWith($ext)) { $isExcludedExtension = $true; break; } }" ^
"    -not ($isExcludedDir -or $isLocalConfig -or $isExcludedFile -or $isExcludedExtension)" ^
"};" ^
"if (-not $files -or $files.Count -eq 0) { throw 'No files selected for deployment package.' }" ^
"Compress-Archive -Path $files.FullName -DestinationPath $destination -CompressionLevel Optimal;" ^
"Write-Host ('Created: ' + $destination);"

if errorlevel 1 (
    echo.
    echo Deployment package creation failed.
    exit /b 1
)

echo.
echo Deployment package created:
echo   deploy\deploy.zip
echo.

endlocal
