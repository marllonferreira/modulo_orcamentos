@echo off
echo ===================================================
echo INSTALADOR DE DEPENDENCIAS (COMPOSER)
echo ===================================================
echo.

:: 1. Adiciona o PHP do Laragon ao PATH temporariamente
:: Ajuste o caminho abaixo se a versao do PHP for diferente
set PATH=%PATH%;c:\laragon\bin\php\php-8.1.10-Win32-vs16-x64

:: 2. Executa o Composer Install
echo Executando 'composer install'...
echo.

call c:\laragon\bin\composer\composer.bat install

echo.
echo ===================================================
if %ERRORLEVEL% EQ 0 (
    echo SUCESSO! As dependencias foram instaladas.
) else (
    echo ERRO! Verifique as mensagens acima.
)
echo ===================================================
pause
