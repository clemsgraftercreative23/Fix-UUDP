# Runs the PHPUnit suite with a PHP 7.4 binary.
#
# Why: this app is Laravel 5.8 / PHPUnit 7.5 (see composer.json), which is
# incompatible with PHP 8+ (PHPUnit 7.5's Configuration.php crashes on PHP 8
# with "Cannot acquire reference to $GLOBALS"). If the machine's default
# `php` on PATH is 8.x, running `vendor\bin\phpunit` directly will fail or
# hang. Set $env:PHP74_BIN to override the auto-detected path.
#
# Usage:
#   ./scripts/run-tests.ps1                       # run everything
#   ./scripts/run-tests.ps1 --testsuite Unit       # just the Unit suite
#   ./scripts/run-tests.ps1 --filter TravelDayTotal
param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$PhpUnitArgs
)

$candidates = @(@(
    $env:PHP74_BIN,
    "C:\laragon\bin\php\php-7.4.33-Win32-vc15-x64\php.exe",
    "$env:USERPROFILE\scoop\apps\php74\current\php.exe"
) | Where-Object { $_ -and (Test-Path $_) })

if ($candidates.Count -eq 0) {
    Write-Error "Could not find a PHP 7.4 binary. Set `$env:PHP74_BIN to its full path and retry."
    exit 1
}

$php = $candidates[0]
$repoRoot = Split-Path -Parent $PSScriptRoot

if ($PhpUnitArgs.Count -eq 0) {
    $PhpUnitArgs = @("--testsuite", "Unit")
}

& $php "$repoRoot\vendor\bin\phpunit" @PhpUnitArgs
exit $LASTEXITCODE
