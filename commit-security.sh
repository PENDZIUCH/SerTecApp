#!/bin/bash

# Script para hacer commit de cambios de seguridad
# Uso: bash commit-security.sh

echo "🔒 Committing security improvements..."

# Stage files
git add .gitignore
git add backend-laravel/.env.example
git add sertecapp-tecnicos/.env.example
git add SECURITY_FLOW.md
git add SETUP_LOCAL.md

# Commit
git commit -m "[SECURITY] Agregar flujo de seguridad y archivos de configuración

- Mejorar .gitignore para permitir memory files documentación
- Crear .env.example template para backend (Laravel)
- Crear .env.example template para frontend (Next.js)
- Agregar SECURITY_FLOW.md con guía de commits seguros
- Agregar SETUP_LOCAL.md con instrucciones de setup

Cambios:
✅ Proteger secretos (.env, API keys, passwords)
✅ Template .env.example para nuevos devs
✅ Documentar estructura de commits
✅ Checklist pre-commit para verificar cambios
✅ Ejemplos de commits seguros

Co-Authored-By: Claude <claude@anthropic.com>"

echo "✅ Commit completado!"
git log --oneline -1
