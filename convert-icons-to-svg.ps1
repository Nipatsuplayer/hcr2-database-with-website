# PNG to SVG Converter - PowerShell Script
# Requires ImageMagick and Potrace for high-quality vector conversion

param(
    [switch]$Force,
    [switch]$Verbose
)

# Configuration
$Directories = @(
    "img\map_icons",
    "img\vehicle_icons",
    "img\tuning_parts_icons"
)

$TempDir = "$env:TEMP\svg_conversion"
$LogFile = "svg_conversion_log.txt"

# Colors for output
$Green = "Green"
$Yellow = "Yellow"
$Red = "Red"
$Cyan = "Cyan"

function Write-ColorOutput {
    param([string]$Message, [string]$Color = "White")
    Write-Host $Message -ForegroundColor $Color
}

function Write-Log {
    param([string]$Message)
    $Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    "$Timestamp - $Message" | Out-File -FilePath $LogFile -Append
    if ($Verbose) {
        Write-ColorOutput $Message
    }
}

function Test-Command {
    param([string]$Command)
    try {
        $null = Get-Command $Command -ErrorAction Stop
        return $true
    } catch {
        return $false
    }
}

function Get-PngFiles {
    param([string]$Directory)
    if (Test-Path $Directory) {
        return Get-ChildItem -Path $Directory -Filter "*.png" -File
    }
    return @()
}

function Convert-PngToSvg {
    param([string]$PngPath, [string]$SvgPath)

    $fileName = [System.IO.Path]::GetFileNameWithoutExtension($PngPath)
    $directory = [System.IO.Path]::GetDirectoryName($PngPath)

    Write-Log "Converting $fileName.png to SVG..."

    try {
        # Determine target size based on directory
        $targetSize = "20x20"
        if ($directory -like "*tuning_parts_icons*") {
            $targetSize = "20x20"  # Same size as vehicle/map icons for consistency
        }

        # Step 1: Resize and convert PNG to BMP (Potrace works better with BMP)
        $bmpPath = "$TempDir\$fileName.bmp"
        & magick convert "$PngPath" -background white -alpha remove -resize $targetSize "$bmpPath"
        if ($LASTEXITCODE -ne 0) { throw "ImageMagick convert failed" }

        # Step 2: Convert BMP to PBM (Portable Bitmap)
        $pbmPath = "$TempDir\$fileName.pbm"
        & magick convert "$bmpPath" -colorspace Gray -threshold 50% "$pbmPath"
        if ($LASTEXITCODE -ne 0) { throw "ImageMagick threshold failed" }

        # Step 3: Use Potrace to convert PBM to SVG
        & potrace "$pbmPath" -s -o "$SvgPath"
        if ($LASTEXITCODE -ne 0) { throw "Potrace conversion failed" }

        # Clean up temp files
        Remove-Item $bmpPath -ErrorAction SilentlyContinue
        Remove-Item $pbmPath -ErrorAction SilentlyContinue

        Write-Log "Successfully converted $fileName.png to $fileName.svg (${targetSize})"
        return $true

    } catch {
        Write-Log "ERROR: Failed to convert $fileName.png - $($_.Exception.Message)"

        # Fallback: Try direct ImageMagick conversion without Potrace
        try {
            Write-Log "Attempting fallback conversion for $fileName.png..."
            & magick convert "$PngPath" -resize $targetSize "$SvgPath"
            if ($LASTEXITCODE -eq 0) {
                Write-Log "Fallback conversion successful for $fileName.png (${targetSize})"
                return $true
            }
        } catch {
            Write-Log "Fallback conversion also failed for $fileName.png"
        }

        return $false
    }
}

# Main script
Write-ColorOutput "========================================" $Cyan
Write-ColorOutput "PNG to SVG Icon Converter (PowerShell)" $Cyan
Write-ColorOutput "========================================" $Cyan
Write-ColorOutput ""

# Check if we're in the right directory
if (-not (Test-Path "img\map_icons") -or -not (Test-Path "img\vehicle_icons") -or -not (Test-Path "img\tuning_parts_icons")) {
    Write-ColorOutput "ERROR: Required directories not found!" $Red
    Write-ColorOutput "This script must be run from the project root directory." $Red
    Write-ColorOutput "Expected directories: img\map_icons, img\vehicle_icons, img\tuning_parts_icons" $Red
    exit 1
}

# Check for required tools
$hasMagick = Test-Command "magick"
$hasPotrace = Test-Command "potrace"

Write-ColorOutput "Checking for required tools..." $Yellow
Write-ColorOutput "ImageMagick (magick): $(if ($hasMagick) { "Found" } else { "NOT FOUND" })" $(if ($hasMagick) { $Green } else { $Red })
Write-ColorOutput "Potrace: $(if ($hasPotrace) { "Found" } else { "NOT FOUND" })" $(if ($hasPotrace) { $Green } else { $Red })

if (-not $hasMagick) {
    Write-ColorOutput "" $Red
    Write-ColorOutput "ERROR: ImageMagick is required but not found!" $Red
    Write-ColorOutput "Please install ImageMagick from: https://imagemagick.org/download.php" $Red
    Write-ColorOutput "Make sure to check 'Add to PATH' during installation." $Red
    exit 1
}

if (-not $hasPotrace) {
    Write-ColorOutput "" $Yellow
    Write-ColorOutput "WARNING: Potrace not found. Will use fallback conversion method." $Yellow
    Write-ColorOutput "For best results, install Potrace from: http://potrace.sourceforge.net/" $Yellow
    Write-ColorOutput "" $Yellow
}

# Create temp directory
if (-not (Test-Path $TempDir)) {
    New-Item -ItemType Directory -Path $TempDir | Out-Null
}

# Clear previous log
if (Test-Path $LogFile) {
    Remove-Item $LogFile
}

Write-Log "Starting PNG to SVG conversion process"

# Count total files
$totalFiles = 0
$convertedFiles = 0
$skippedFiles = 0

foreach ($dir in $Directories) {
    $pngFiles = Get-PngFiles $dir
    $totalFiles += $pngFiles.Count
}

Write-ColorOutput "Found $totalFiles PNG files to process" $Green
Write-ColorOutput ""

# Process each directory
foreach ($dir in $Directories) {
    Write-ColorOutput "Processing $dir..." $Cyan

    $pngFiles = Get-PngFiles $dir
    if ($pngFiles.Count -eq 0) {
        Write-ColorOutput "  No PNG files found in $dir" $Yellow
        continue
    }

    foreach ($pngFile in $pngFiles) {
        $svgPath = [System.IO.Path]::ChangeExtension($pngFile.FullName, ".svg")

        if ((Test-Path $svgPath) -and -not $Force) {
            Write-Log "Skipped $($pngFile.Name) - SVG already exists (use -Force to overwrite)"
            $skippedFiles++
            continue
        }

        if (Convert-PngToSvg $pngFile.FullName $svgPath) {
            $convertedFiles++
        }
    }
}

# Cleanup
if (Test-Path $TempDir) {
    Remove-Item $TempDir -Recurse -ErrorAction SilentlyContinue
}

# Summary
Write-ColorOutput "" $Cyan
Write-ColorOutput "Conversion Summary:" $Cyan
Write-ColorOutput "===================" $Cyan
Write-ColorOutput "Total PNG files found: $totalFiles" $Green
Write-ColorOutput "Successfully converted: $convertedFiles" $Green
Write-ColorOutput "Skipped (SVG exists): $skippedFiles" $Yellow
Write-ColorOutput "Failed conversions: $($totalFiles - $convertedFiles - $skippedFiles)" $(if (($totalFiles - $convertedFiles - $skippedFiles) -eq 0) { $Green } else { $Red })

if (Test-Path $LogFile) {
    Write-ColorOutput "" $Cyan
    Write-ColorOutput "Detailed log saved to: $LogFile" $Cyan
}

Write-ColorOutput "" $Green
Write-ColorOutput "Conversion process completed!" $Green

if (-not $Verbose) {
    Write-ColorOutput "Use -Verbose switch to see detailed conversion progress." $Yellow
}