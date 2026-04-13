@echo off
REM Script para hacer commit de cambios de seguridad en Windows

echo.
echo ===================================
echo 🔒 Committing Security Improvements
echo ===================================
echo.

cd /d "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp"

echo Adding files...
git add .gitignore
git add backend-laravel\.env.example
git add sertecapp-tecnicos\.env.example
git add SECURITY_FLOW.md
git add SETUP_LOCAL.md

echo.
echo Committing...
git commit -m "[SECURITY] Agregar flujo de seguridad y archivos de configuracion - Mejorar .gitignore para permitir memory files documentacion - Crear .env.example template para backend (Laravel) - Crear .env.example template para frontend (Next.js) - Agregar SECURITY_FLOW.md con guia de commits seguros - Agregar SETUP_LOCAL.md con instrucciones de setup - Proteger secretos (.env, API keys, passwords) - Template .env.example para nuevos devs - Documentar estructura de commits - Checklist pre-commit para verificar cambios - Ejemplos de commits seguros Co-Authored-By: Claude"

echo.
echo ✅ Commit completado!
echo.
git log --oneline -1

echo.
pause
