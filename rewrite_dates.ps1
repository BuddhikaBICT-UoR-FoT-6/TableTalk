#!/usr/bin/env pwsh

$envFilter = @"
export GIT_AUTHOR_DATE=`echo `$GIT_AUTHOR_DATE | sed 's/2026/2025/g'`
export GIT_COMMITTER_DATE=`echo `$GIT_COMMITTER_DATE | sed 's/2026/2025/g'`
"@

git filter-branch --force --env-filter $envFilter -- --all
git push --force origin master
Write-Host "Git history rewrite complete!"
