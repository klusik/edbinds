# Elite Bindings Vault deployment package builder.
# This script is called by deploy.bat and can also be run directly from PowerShell.

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $ProjectRoot

$DeployDirectory = Join-Path $ProjectRoot 'deploy'
$DestinationZip = Join-Path $DeployDirectory 'deploy.zip'
$StagingDirectory = Join-Path $env:TEMP ('elite-bindings-vault-deploy-' + [Guid]::NewGuid().ToString('N'))

$ExcludedRootDirectories = @(
    '.git',
    '.github',
    '.idea',
    '.vscode',
    'deploy',
    'data'
)

$ExcludedDirectoryPrefixes = @(
    'storage\uploads\',
    'storage\cache\',
    'storage\logs\',
    'uploads\',
    'cache\',
    'logs\'
)

$ExcludedExactFiles = @(
    'config\config.php',
    'Thumbs.db',
    '.DS_Store'
)

$ExcludedSuffixes = @(
    '.log',
    '.zip',
    '.sql.gz',
    '.sql.zip',
    '.dump'
)

function Test-DeployExcludedPath {
    param(
        [Parameter(Mandatory = $true)]
        [string] $RelativePath
    )

    $normalized = $RelativePath.Replace('/', '\')
    $parts = $normalized -split '\\'

    foreach ($directory in $ExcludedRootDirectories) {
        if ($parts.Length -gt 0 -and $parts[0].Equals($directory, [System.StringComparison]::OrdinalIgnoreCase)) {
            return $true
        }
    }

    foreach ($prefix in $ExcludedDirectoryPrefixes) {
        if ($normalized.StartsWith($prefix, [System.StringComparison]::OrdinalIgnoreCase)) {
            return $true
        }
    }

    foreach ($fileName in $ExcludedExactFiles) {
        if ($normalized.Equals($fileName, [System.StringComparison]::OrdinalIgnoreCase)) {
            return $true
        }
    }

    if ($normalized -like 'config\*.local.php') {
        return $true
    }

    $lower = $normalized.ToLowerInvariant()
    foreach ($suffix in $ExcludedSuffixes) {
        if ($lower.EndsWith($suffix)) {
            return $true
        }
    }

    return $false
}

if (-not (Test-Path $DeployDirectory)) {
    New-Item -ItemType Directory -Path $DeployDirectory -Force | Out-Null
}

$Gitkeep = Join-Path $DeployDirectory '.gitkeep'
if (-not (Test-Path $Gitkeep)) {
    New-Item -ItemType File -Path $Gitkeep -Force | Out-Null
}

if (Test-Path $DestinationZip) {
    Remove-Item $DestinationZip -Force
}

New-Item -ItemType Directory -Path $StagingDirectory -Force | Out-Null

try {
    $files = Get-ChildItem -Path $ProjectRoot -Recurse -File -Force | Where-Object {
        $relative = $_.FullName.Substring($ProjectRoot.Length + 1)
        -not (Test-DeployExcludedPath -RelativePath $relative)
    }

    if ($null -eq $files -or $files.Count -eq 0) {
        throw 'No files selected for deployment package.'
    }

    foreach ($file in $files) {
        $relative = $file.FullName.Substring($ProjectRoot.Length + 1)
        $target = Join-Path $StagingDirectory $relative
        $targetDirectory = Split-Path $target -Parent

        if (-not (Test-Path $targetDirectory)) {
            New-Item -ItemType Directory -Path $targetDirectory -Force | Out-Null
        }

        Copy-Item -LiteralPath $file.FullName -Destination $target -Force
    }

    Compress-Archive -Path (Join-Path $StagingDirectory '*') -DestinationPath $DestinationZip -CompressionLevel Optimal -Force

    if (-not (Test-Path $DestinationZip)) {
        throw 'deploy\deploy.zip was not created.'
    }

    $archive = Get-Item $DestinationZip
    if ($archive.Length -le 0) {
        throw 'deploy\deploy.zip was created but is empty.'
    }

    Write-Host ('Created: ' + $DestinationZip)
    Write-Host ('Size:    ' + $archive.Length + ' bytes')
    Write-Host ('Files:   ' + $files.Count)
}
finally {
    if (Test-Path $StagingDirectory) {
        Remove-Item $StagingDirectory -Recurse -Force
    }
}
