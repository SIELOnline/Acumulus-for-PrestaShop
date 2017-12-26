@echo off
rem Check usage and arguments.
if dummy==dummy%1 (
echo Usage: %~n0 version
exit /B 1;
)
set version=%1
set archive=PrestaShop-1.7.x-Acumulus-Customise-Invoice-%version%.zip

rem delete, recreate and check zip package.
del %archive% 2> nul
"C:\Program Files\7-Zip\7z.exe" a -tzip %archive% acumulus_customise_invoice | findstr /i "Failed Error"
"C:\Program Files\7-Zip\7z.exe" t %archive% | findstr /i "Processing Everything Failed Error"
