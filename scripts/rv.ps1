# Rendezvous CLI Utility Script (scripts/rv.ps1) for Windows PowerShell

param (
    [Parameter(Position=0)]
    [string]$Command = "help",
    [Parameter(ValueFromRemainingArguments=$true)]
    [string[]]$Args
)

# Detect Docker Compose binary (v2 'docker compose' vs v1 'docker-compose')
$COMPOSE = "docker compose"
try {
    $null = & docker compose version 2>$null
} catch {
    $COMPOSE = "docker-compose"
}

switch ($Command.ToLower()) {
    "up" {
        Write-Host "Starting Rendezvous Docker containers..." -ForegroundColor Green
        Invoke-Expression "$COMPOSE up -d --build"
        Write-Host "Rendezvous web running at: http://localhost:8080" -ForegroundColor Cyan
        Write-Host "Mailpit UI running at:     http://localhost:8025" -ForegroundColor Cyan
    }
    "down" {
        Write-Host "Stopping Rendezvous Docker containers..." -ForegroundColor Yellow
        Invoke-Expression "$COMPOSE down"
    }
    "build" {
        Write-Host "Building frontend assets and optimizing backend..." -ForegroundColor Green
        Invoke-Expression "$COMPOSE exec node npm run build"
        Invoke-Expression "$COMPOSE exec app php artisan config:cache"
        Invoke-Expression "$COMPOSE exec app php artisan route:cache"
        Write-Host "Containerized build completed successfully!" -ForegroundColor Cyan
    }
    "test" {
        Write-Host "Running complete PHPUnit & Pest test suite..." -ForegroundColor Green
        $extraArgs = $Args -join " "
        Invoke-Expression "$COMPOSE exec app php artisan test $extraArgs"
    }
    "artisan" {
        $extraArgs = $Args -join " "
        Invoke-Expression "$COMPOSE exec app php artisan $extraArgs"
    }
    "composer" {
        $extraArgs = $Args -join " "
        Invoke-Expression "$COMPOSE exec app composer $extraArgs"
    }
    "npm" {
        $extraArgs = $Args -join " "
        Invoke-Expression "$COMPOSE exec node npm $extraArgs"
    }
    "logs" {
        $extraArgs = $Args -join " "
        Invoke-Expression "$COMPOSE logs -f $extraArgs"
    }
    "release" {
        Write-Host "Packaging release bundle for Hostinger Shared Hosting..." -ForegroundColor Green
        New-Item -ItemType Directory -Force -Path "dist_release" | Out-Null
        
        # Build frontend assets inside container
        Invoke-Expression "$COMPOSE exec node npm run build"
        
        # Create staging directory
        $stageDir = "dist_release\staging"
        if (Test-Path $stageDir) { Remove-Item -Recurse -Force $stageDir }
        New-Item -ItemType Directory -Force -Path $stageDir | Out-Null
        
        # Copy backend, public static assets and config
        Copy-Item -Recurse -Force "app", "bootstrap", "config", "database", "public", "routes", "vendor", "composer.json" -Destination $stageDir
        if (Test-Path ".htaccess") { Copy-Item -Force ".htaccess" -Destination $stageDir }
        
        # Compress into zip package
        $zipFile = "dist_release\rendezvous-release.zip"
        if (Test-Path $zipFile) { Remove-Item -Force $zipFile }
        Compress-Archive -Path "$stageDir\*" -DestinationPath $zipFile -Force
        
        # Generate SHA256 Checksum
        $hash = (Get-FileHash $zipFile -Algorithm SHA256).Hash
        "$hash  rendezvous-release.zip" | Out-File -Encoding ascii "dist_release\SHA256SUMS"
        
        Remove-Item -Recurse -Force $stageDir
        Write-Host "Release bundle created: dist_release\rendezvous-release.zip" -ForegroundColor Cyan
        Write-Host "SHA256 Checksum: $hash" -ForegroundColor Yellow
    }
    "deploy" {
        # Deploy alias pointing to release packaging for Hostinger Shared Hosting
        & $PSCommandPath "release"
    }
    Default {
        Write-Host "Rendezvous CLI Manager" -ForegroundColor Green
        Write-Host "Usage: .\rv.ps1 {up|down|build|test|artisan|composer|npm|logs|release|deploy}" -ForegroundColor Yellow
    }
}
