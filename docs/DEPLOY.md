# Deploy

`deploy.sh`, na raiz, roda no servidor de produção. Não execute esse script na cópia local: ele coloca a aplicação em manutenção, faz `git pull` e publica o cache de configuração e de rotas.

No servidor, a primeira vez precisa de `chmod +x deploy.sh`. A sequência do script é:

1. Copia `database/database.sqlite` para um backup com data e hora.
2. `php artisan down`
3. `git pull`
4. `composer install --no-dev --optimize-autoloader`
5. `php artisan migrate --force`
6. `php artisan config:cache`, `route:clear`, `route:cache` e `view:cache`
7. `php artisan up`

Rota nova que não aparece depois do deploy: o cache de rotas ficou antigo. O próprio script já limpa e recria esse cache. Fora do deploy, `php artisan route:clear` resolve.

O arquivo do banco, o journal e os backups (`*.sqlite`, `*.backup`, `*.backup.*`) estão em `database/.gitignore`. Backup de produção fica no servidor. Não copie esse arquivo para o Git nem para um lugar público.

A versão que o usuário vê no painel não sai deste script. Ela está em `HomeController::systemInfo()`.
