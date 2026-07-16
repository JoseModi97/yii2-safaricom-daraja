param(
    [string]$Repo = "JoseModi97/yii2-safaricom-daraja",
    [string]$Tag = "v1.0.0"
)

$ErrorActionPreference = "Stop"

Write-Host "Checking GitHub CLI authentication..."
gh auth status *> $null
if ($LASTEXITCODE -ne 0) {
    Write-Host "GitHub is not authenticated. A browser/device login will start now."
    gh auth login -h github.com --web --git-protocol https
}

Write-Host "Ensuring git remote is set to https://github.com/$Repo.git"
$origin = git remote get-url origin 2>$null
if ($LASTEXITCODE -ne 0) {
    git remote add origin "https://github.com/$Repo.git"
} elseif ($origin -ne "https://github.com/$Repo.git") {
    git remote set-url origin "https://github.com/$Repo.git"
}

Write-Host "Checking if GitHub repository exists..."
gh repo view $Repo *> $null
if ($LASTEXITCODE -ne 0) {
    Write-Host "Creating public GitHub repository $Repo..."
    gh repo create $Repo --public --source . --remote origin --push
} else {
    Write-Host "Repository exists. Pushing main..."
    git push -u origin main
}

Write-Host "Creating/pushing release tag $Tag..."
git rev-parse $Tag *> $null
if ($LASTEXITCODE -ne 0) {
    git tag $Tag
}
git push origin $Tag

Write-Host ""
Write-Host "Done."
Write-Host "GitHub: https://github.com/$Repo"
Write-Host "Packagist submit URL: https://packagist.org/packages/submit"
Write-Host "Composer package: josemodi97/yii2-safaricom-daraja"
