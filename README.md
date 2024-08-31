[![Run Tests](https://github.com/slava-vishnyakov/caprover-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/slava-vishnyakov/caprover-laravel/actions/workflows/tests.yml)

# Creates the project with all scaffolding for Caprover deployment.

```
composer global require laravel/installer:dev-master

git clone git@github.com:slava-vishnyakov/caprover-laravel.git
(cd caprover-laravel && composer install)
caprover-laravel/caprover-laravel new project project.com --migrator
```

Creates a `project` folder suitable for deployment to `project.com`

See the generated `caprover-deploy.txt` file for complete instructions.
