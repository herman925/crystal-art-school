@echo off
setlocal enabledelayedexpansion

pushd "%~dp0"

for %%P in (8080 8081 8082 8083 8084) do (
    netstat -an | findstr ":%%P " | findstr "LISTENING" >nul 2>&1
    if errorlevel 1 (
        echo Serving site at http://localhost:%%P
        python -m http.server %%P
        popd
        exit /b
    )
)

echo All ports 8080-8084 in use.
popd
pause
