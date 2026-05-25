@echo off
setlocal

set COMPOSE_FILE=docker-compose.demo.yml
set ENV_FILE=.env.demo
set ENV_EXAMPLE=.env.demo.example
set APP_URL=http://localhost:8080

if not exist "%ENV_FILE%" (
    if not exist "%ENV_EXAMPLE%" (
        echo Missing %ENV_EXAMPLE%. Cannot create local demo environment file.
        exit /b 1
    )
    copy "%ENV_EXAMPLE%" "%ENV_FILE%" >nul
    echo Created %ENV_FILE% from %ENV_EXAMPLE%.
)

docker info >nul 2>&1
if errorlevel 1 (
    echo Docker is not running. Please start Docker Desktop and run this file again.
    exit /b 1
)

docker compose version >nul 2>&1
if errorlevel 1 (
    echo Docker Compose v2 is required.
    echo Please update Docker Desktop or install the Docker Compose plugin.
    exit /b 1
)

echo [1/3] Pulling Besucherportal images...
docker compose --env-file "%ENV_FILE%" -f "%COMPOSE_FILE%" pull
if errorlevel 1 echo Image pull failed. Continuing with locally available images if present.

echo [2/3] Starting Besucherportal...
docker compose --env-file "%ENV_FILE%" -f "%COMPOSE_FILE%" up -d
if errorlevel 1 exit /b 1

echo [3/3] Waiting for the portal to become reachable...
powershell -NoProfile -ExecutionPolicy Bypass -Command "$url='%APP_URL%'; for ($i=1; $i -le 60; $i++) { try { $r = Invoke-WebRequest -UseBasicParsing -Uri $url -TimeoutSec 2; if ($r.StatusCode -lt 500) { exit 0 } } catch {}; Start-Sleep -Seconds 2 }; exit 1"
if errorlevel 1 (
    echo The containers started, but the app did not answer in time.
    echo Run: docker compose --env-file %ENV_FILE% -f %COMPOSE_FILE% logs --tail=120 app
    exit /b 1
)

echo.
echo +------------------------------------------------------------+
echo ^| SUCCESS! Besucherportal is running.                         ^|
echo ^|                                                            ^|
echo ^| App:      http://localhost:8080                            ^|
echo ^| Mailhog:  http://localhost:8025                            ^|
echo ^|                                                            ^|
echo ^| Login:    admin@example.org                                ^|
echo ^| Password: ChangeMe-42!                                     ^|
echo +------------------------------------------------------------+
echo.

endlocal
