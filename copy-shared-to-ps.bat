@echo off
rem Link Common library to here.
mklink /J D:\Projecten\Acumulus\Webkoppelingen\PrestaShop\acumulus\libraries\siel\acumulus D:\Projecten\Acumulus\Webkoppelingen\libAcumulus

rem Link license files to here.
mklink /H D:\Projecten\Acumulus\Webkoppelingen\PrestaShop\acumulus\license.txt D:\Projecten\Acumulus\Webkoppelingen\libAcumulus\license.txt
mklink /H D:\Projecten\Acumulus\Webkoppelingen\PrestaShop\acumulus\licentie-nl.pdf D:\Projecten\Acumulus\Webkoppelingen\libAcumulus\licentie-nl.pdf
mklink /H D:\Projecten\Acumulus\Webkoppelingen\PrestaShop\acumulus\leesmij.txt D:\Projecten\Acumulus\Webkoppelingen\leesmij.txt
