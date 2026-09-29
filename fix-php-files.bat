@echo off
echo Correction des fichiers PHP...

cd app\Http\Controllers\Api
powershell -Command "(Get-Content AuthController.php -Raw) -replace '^\xEF\xBB\xBF','' | Set-Content AuthController.php -NoNewline"

cd ..\..\..\Mail
powershell -Command "(Get-Content InscriptionStatusEmail.php -Raw) -replace '^\xEF\xBB\xBF','' | Set-Content InscriptionStatusEmail.php -NoNewline"
powershell -Command "(Get-Content VerificationEmail.php -Raw) -replace '^\xEF\xBB\xBF','' | Set-Content VerificationEmail.php -NoNewline"
powershell -Command "(Get-Content WelcomeEmail.php -Raw) -replace '^\xEF\xBB\xBF','' | Set-Content WelcomeEmail.php -NoNewline"

cd ..\Models
powershell -Command "(Get-Content Inscription.php -Raw) -replace '^\xEF\xBB\xBF','' | Set-Content Inscription.php -NoNewline"
powershell -Command "(Get-Content User.php -Raw) -replace '^\xEF\xBB\xBF','' | Set-Content User.php -NoNewline"
powershell -Command "(Get-Content UserProfile.php -Raw) -replace '^\xEF\xBB\xBF','' | Set-Content UserProfile.php -NoNewline"

cd ..\Services
powershell -Command "(Get-Content AuthService.php -Raw) -replace '^\xEF\xBB\xBF','' | Set-Content AuthService.php -NoNewline"

echo Fichiers PHP corriges.
pause
