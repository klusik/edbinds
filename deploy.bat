@echo off
setlocal EnableExtensions

REM ===========================================================
REM Elite Bindings Vault deployment package
REM ===========================================================

set "PROJECT_ROOT=%~dp0"
cd /d "%PROJECT_ROOT%"

if not exist "deploy" mkdir "deploy"

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%PROJECT_ROOT%tools\deploy.ps1"
set "DEPLOY_EXIT_CODE=%ERRORLEVEL%"

if not "%DEPLOY_EXIT_CODE%"=="0" (
    echo.
    echo Deployment package creation failed.
    exit /b %DEPLOY_EXIT_CODE%
)

if not exist "deploy\deploy.zip" (
    echo.
    echo Deployment package creation failed: deploy\deploy.zip does not exist.
    exit /b 1
)

echo.
echo Deployment package created:
echo   deploy\deploy.zip
echo.

exit /b 0
