@echo off
REM ============================================================
REM URGENT: Fix GitHub Push - Get all 1653 files to GitHub NOW
REM ============================================================

cd /d c:\opt\kotvrf\CatVRF

echo.
echo ========================================
echo 1. Checking current branch and commits
echo ========================================
git branch -v -a
git log --oneline -1

echo.
echo ========================================
echo 2. Checking remote origin
echo ========================================
git remote -v

echo.
echo ========================================
echo 3. PUSHING to origin/master (main branch exists locally)
echo ========================================
REM The problem: origin/main doesn't exist, but origin/master does
REM Solution: Push main branch to origin/master
git push origin main:master --force -v

echo.
echo ========================================
echo 4. Verify push was successful
echo ========================================
git status
git branch -r

echo.
echo ========================================
echo NEXT STEP: Check GitHub repository
echo URL: https://github.com/iyegorovskyi_clemny/CatVRF
echo ========================================
pause
