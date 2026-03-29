@echo off
cd /d c:\opt\kotvrf\CatVRF

echo Проверяю ветки...
git branch -a

echo.
echo Отправляю на origin/master...
git push origin main:master --force

echo.
echo Статус после push:
git status

echo.
echo Готово!
pause
