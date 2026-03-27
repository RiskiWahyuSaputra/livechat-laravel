$ErrorActionPreference = "Stop"
git checkout main -f

$branches = @("main2", "main3", "main4")
foreach ($b in $branches) {
    git checkout $b -f
    git merge main
    git push origin $b
}

git checkout main
Write-Host "All branches pushed successfully!"
