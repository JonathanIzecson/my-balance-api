# My Balance App

Aplicación personal para gestionar balances financieros.

## Requisitos
- PHP 8.3+
- Composer

## Uso
Descripción breve de las funcionalidades principales.

## Desarrollo con Sail / Devcontainer

### Requisitos
- Docker Desktop (o Docker en WSL2)
- Cursor o VS Code con extensión Dev Containers

### Primera vez (sin devcontainer)
1. `composer install`
2. `cp .env.example .env`
3. `./vendor/bin/sail up -d`
4. `./vendor/bin/sail artisan key:generate`
5. `./vendor/bin/sail artisan migrate`

### Con devcontainer (recomendado)
1. Abrir el proyecto en Cursor
2. `Dev Containers: Reopen in Container`
3. Esperar a que termine `postCreateCommand`
4. Abrir `http://localhost` en el navegador