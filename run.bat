@echo off

GOTO Start

:Start
cls
echo --------Minify and Combine Js/Css files with YUI Library--------
echo.
php compress.php
echo. 
echo Script done. Please check file debug.txt for more informations. 
echo.
GOTO Choise

:Choise
SET Choice=
SET /P Choice= You can press C to compress again (and something else to quit):
IF "%Choice%"=="c" GOTO Start
GOTO End

:End