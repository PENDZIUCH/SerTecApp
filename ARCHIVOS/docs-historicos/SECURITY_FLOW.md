# 🔒 Security Flow para Commits - SerTecApp

## Principios Clave

1. **Nunca commitear secretos** - .env, API keys, passwords, tokens
2. **Revisar antes de commitear** - No ir rápido, revisar cambios
3. **Un commit = Un cambio** - Commits pequeños y enfocados
4. **Documentar siempre** - Qué cambió y por qué en el mensaje

---

## 📋 Checklist Pre-Commit

Antes de hacer `git commit`:

```bash
# 1. Ver qué archivos cambiaron
git status

# 2. Revisar cambios línea por línea
git diff

# 3. Verificar que NO hay:
# ❌ .env con passwords
# ❌ API keys, tokens, secretos
# ❌ node_modules, vendor, build/
# ❌ Archivos accidentales
```

---

## ✅ Archivos Seguros para Commitear

- `*.md` - Documentación
- `*.tsx`, `*.ts`, `*.php` - Código
- `.env.example` - Template SIN valores
- `package.json`, `composer.json` - Deps (sin lock)
- `.gitignore`, `.editorconfig` - Config

## ❌ NUNCA Commitear

- `.env`, `.env.local`, `.env.*.local` - Usa .env.example
- `*.key`, `*.pem` - Certificados/claves
- `node_modules/`, `vendor/` - Dependencies
- `.next/`, `build/`, `dist/` - Build outputs
- `storage/logs/*` - Logs
- `.idea/`, `.vscode/` - IDE internals

---

## 📝 Estructura del Commit

### Formato

```
[TIPO] Descripción corta (máx 50 chars)

Descripción detallada si es necesario (opcional)
- Cambio 1
- Cambio 2
- Por qué se hace

Co-Authored-By: Claude <claude@anthropic.com>
```

### TIPOS

- `[FEAT]` - Nueva funcionalidad
- `[FIX]` - Bug fix
- `[DOCS]` - Documentación
- `[REFACTOR]` - Cambio de código sin función nueva
- `[SECURITY]` - Fix de seguridad
- `[CHORE]` - Setup, dependencies, config

### Ejemplos

**Bien:**
```
[FIX] Validación mínima de password en backend

- Cambiar min:8 en UserController
- Cambiar validación frontend a minLength=8
- Actualizar changelog

Fixes: validation.min.string error
Co-Authored-By: Claude <claude@anthropic.com>
```

**Mal:**
```
update code
Fixed stuff
wip
```

---

## 🔐 Secrets & Credentials - Manejo

### Para Configuración Sensible

**Local development:**
1. Crear `.env.local` (ignorado por git)
2. Copiar valores de `.env.example`
3. Llenar con datos LOCALES SOLAMENTE

**Production/Staging:**
1. Usar variables de entorno en plataforma (Hostinger, Cloudflare, etc)
2. NUNCA en repo
3. NUNCA en commits

**Si accidentalmente commitiste un secret:**
```bash
# ⚠️ ES SEVERO - el secret ya está en historio

# Opción 1: BFG Repo Cleaner (recomendado)
bfg --delete-files .env <repo-path>

# Opción 2: git filter-branch
git filter-branch --tree-filter 'rm -f .env' HEAD

# Después: force push (¡CUIDADO!)
git push origin --force-with-lease
```

---

## 📊 Workflow Estándar

```bash
# 1. Crear feature branch
git checkout -b feature/user-roles

# 2. Hacer cambios...

# 3. Antes de commitear
git status          # Ver qué cambió
git diff            # Revisar cambios

# 4. Stagear cambios
git add sertecapp-tecnicos/app/...
git add backend-laravel/app/...

# 5. Hacer commit
git commit -m "[FEAT] Agregar estructura de roles

- Crear superadmin, admin, supervisor, tecnico roles
- Implementar Policies en Filament
- Actualizar seeder con roles consolidados

Co-Authored-By: Claude <claude@anthropic.com>"

# 6. Push a rama
git push origin feature/user-roles

# 7. (Opcional) Pull request para code review
```

---

## 🛡️ Protecciones Automáticas (Futuros)

Cuando sea posible implementar:

- **Pre-commit hooks** - Verificar que no hay .env, keys, etc
- **Git secrets** - Escanear para patrones de passwords
- **Branch protection** - Require code review en main/production
- **Signed commits** - GPG signing para verified commits

---

## 📚 Referencias

- Conventional Commits: https://www.conventionalcommits.org/
- GitHub Security: https://docs.github.com/en/code-security
- Git Documentation: https://git-scm.com/doc
