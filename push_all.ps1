git add .
git commit -m "feat: Add interactive YouTube Video Player in chat menu"
git push origin main

$branches = @("main2", "main3", "main4")
foreach ($b in $branches) {
    git checkout $b
    if ($LASTEXITCODE -ne 0) {
        git checkout -b $b
    }
    git merge main
    git push origin $b
}

git checkout main
Write-Host "All branches pushed successfully!"
