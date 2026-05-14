@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
set "DATA_DIR=%SCRIPT_DIR%data"

if not exist "%DATA_DIR%" mkdir "%DATA_DIR%"

docker run -p 3111:3111 -p 49134:49134 ^
    -v "%SCRIPT_DIR%iii-config.yaml:/app/iii-config.yaml:ro" ^
    -v "%SCRIPT_DIR%.env:/app/.env:ro" ^
    -v "%DATA_DIR%:/app/data" ^
    iiidev/iii:latest ^
    --config /app/iii-config.yaml

endlocal