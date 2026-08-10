# Rendezvous CLI Utility Script (rv.ps1) for Windows PowerShell

param (
    [Parameter(Mandatory=$true, Position=0)]
    [string]$Command,
    [ValueFromRemainingArguments($true)]
    [string[]]$Args
)

$COMPOSE = "docker compose"

switch ($Command) {
    "up" {
        Write-Host "🚀 Starting Rendezvous Docker containers..." -ForegroundColor Green
        Invoke-Expression "$COMPOSE up -d --build"
        Write-Host "✅ Rendezvous environment running at http://localhost:8080" -ForegroundColor Cyan
        Write-Host "📧 Mailpit UI running at http://localhost:8025" -ForegroundColor Cyan
    }
    "down" {
        Write-Host "🛑 Stopping Rendezvous Docker containers..." -ForegroundColor Yellow
        Invoke-Expression "$COMPOSE down"
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
    "test" {
        Write-Host "🧪 Running backend & domain unit tests..." -ForegroundColor Green
        Invoke-Expression "$COMPOSE exec app php artisan test"
    }
    "logs" {
        Invoke-Expression "$COMPOSE logs -f"
    }
    "release" {
        Write-Host "📦 Packaging release for Hostinger Shared Hosting..." -ForegroundColor Green
        New-Item -ItemType Directory -Force -Path "dist_release" | Out-Null
        Invoke-Expression "$COMPOSE exec node npm run build"
        Compress-Archive -Path "public\*","app\*","routes\*","config\*" -DestinationPath "dist_release\rendezvous-release.zip" -Force
        Get-FileHash "dist_release\rendezvous-release.zip" -Algorithm SHA256 | Export-Clixml "dist_release\SHA256SUMS.xml"
        Write-Host "✅ Release zip generated at dist_release\rendezvous-release.zip" -ForegroundColor Cyan
    }
    Default {
        Write-Host "Usage: .\scripts\rv.ps1 {up|down|artisan|composer|npm|test|logs|release}" -ForegroundColor Red
    }
}
