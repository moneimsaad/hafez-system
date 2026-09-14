[CmdletBinding()]
param(
    [string] $OutputDirectory = 'output\infinityfree-package-hardened-final'
)

$ErrorActionPreference = 'Stop'

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$packageRoot = [IO.Path]::GetFullPath((Join-Path $repoRoot $OutputDirectory))
$outputRoot = [IO.Path]::GetFullPath((Join-Path $repoRoot 'output'))

if (-not $packageRoot.StartsWith($outputRoot + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'OutputDirectory must remain inside the repository output directory.'
}

function Copy-Directory {
    param(
        [Parameter(Mandatory)] [string] $Source,
        [Parameter(Mandatory)] [string] $Destination
    )

    New-Item -ItemType Directory -Path $Destination -Force | Out-Null
    Get-ChildItem -LiteralPath $Source -Force | Copy-Item -Destination $Destination -Recurse -Force
}

function Copy-File {
    param(
        [Parameter(Mandatory)] [string] $Source,
        [Parameter(Mandatory)] [string] $Destination
    )

    New-Item -ItemType Directory -Path (Split-Path -Parent $Destination) -Force | Out-Null
    Copy-Item -LiteralPath $Source -Destination $Destination -Force
}

if (Test-Path -LiteralPath $packageRoot) {
    Remove-Item -LiteralPath $packageRoot -Recurse -Force
}

Push-Location $repoRoot
try {
    Write-Host 'Installing production PHP dependencies...'
    # Do not run Laravel's post-autoload discovery against the working tree.
    # Shared IDE/PHP processes can lock bootstrap/cache files on Windows, and
    # the deployment cache should be generated inside the package instead.
    & composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress --no-scripts
    if ($LASTEXITCODE -ne 0) { throw 'Composer production install failed.' }

    Write-Host 'Building production frontend assets...'
    & npm run build
    if ($LASTEXITCODE -ne 0) { throw 'Vite production build failed.' }
}
finally {
    Pop-Location
}

New-Item -ItemType Directory -Path $packageRoot -Force | Out-Null

foreach ($directory in @('app', 'bootstrap', 'config', 'lang', 'resources', 'routes', 'vendor')) {
    Copy-Directory -Source (Join-Path $repoRoot $directory) -Destination (Join-Path $packageRoot $directory)
}

# Only deployment-safe database content is copied; local SQLite files are never included.
foreach ($directory in @('migrations', 'factories', 'seeders')) {
    Copy-Directory -Source (Join-Path $repoRoot "database\$directory") -Destination (Join-Path $packageRoot "database\$directory")
}
Copy-File -Source (Join-Path $repoRoot 'database\.gitignore') -Destination (Join-Path $packageRoot 'database\.gitignore')

# Keep public/index.php and public/build together. Do not copy hot files or storage links.
$publicDestination = Join-Path $packageRoot 'public'
New-Item -ItemType Directory -Path $publicDestination -Force | Out-Null
Get-ChildItem -LiteralPath (Join-Path $repoRoot 'public') -Force |
    Where-Object { $_.Name -notin @('hot', 'storage') } |
    Copy-Item -Destination $publicDestination -Recurse -Force

# Preserve the writable Laravel storage layout without copying local data.
foreach ($relative in @(
    'storage',
    'storage\app',
    'storage\app\private',
    'storage\app\public',
    'storage\framework',
    'storage\framework\cache',
    'storage\framework\cache\data',
    'storage\framework\sessions',
    'storage\framework\testing',
    'storage\framework\views',
    'storage\logs'
)) {
    New-Item -ItemType Directory -Path (Join-Path $packageRoot $relative) -Force | Out-Null
}
Get-ChildItem -LiteralPath (Join-Path $repoRoot 'storage') -Recurse -Force -File -Filter '.gitignore' |
    ForEach-Object {
        $relative = $_.FullName.Substring($repoRoot.Length).TrimStart('\')
        Copy-File -Source $_.FullName -Destination (Join-Path $packageRoot $relative)
    }

foreach ($file in @('artisan', 'composer.json', 'composer.lock')) {
    Copy-File -Source (Join-Path $repoRoot $file) -Destination (Join-Path $packageRoot $file)
}

# The packaged template must match InfinityFree's shared-hosting runtime.
# Keep the repository root template local-development friendly while placing
# the deployment-specific template at the package root for upload use.
Copy-File -Source (Join-Path $repoRoot 'deployment\infinityfree\.env.example') -Destination (Join-Path $packageRoot '.env.example')
Copy-File -Source (Join-Path $repoRoot 'deployment\infinityfree\htdocs.htaccess') -Destination (Join-Path $packageRoot '.htaccess')

# Generate package discovery files in the isolated deployment tree.
Push-Location $packageRoot
try {
    & php artisan package:discover --ansi
    if ($LASTEXITCODE -ne 0) { throw 'Laravel package discovery failed in the deployment package.' }
}
finally {
    Pop-Location
}

# Remove generated runtime data that might have been created during local builds.
foreach ($relative in @('storage\logs', 'storage\app\private', 'storage\app\public', 'storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\testing', 'storage\framework\views')) {
    Get-ChildItem -LiteralPath (Join-Path $packageRoot $relative) -Force -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -ne '.gitignore' } |
        Remove-Item -Recurse -Force
}

$zipPath = "$packageRoot.zip"
if (Test-Path -LiteralPath $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}
Add-Type -AssemblyName System.IO.Compression.FileSystem
[IO.Compression.ZipFile]::CreateFromDirectory($packageRoot, $zipPath, [IO.Compression.CompressionLevel]::Optimal, $false)

Write-Host "Deployment directory: $packageRoot"
Write-Host "Deployment archive: $zipPath"
