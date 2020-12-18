Go to "One-click apps" in caprover, type ">" to see the "TEMPLATE", paste this into template:

------- START HERE
captainVersion: 4
services:
    $$cap_appname:
        caproverExtra:
            dockerfileLines:
                - FROM busybox:1
        environment:
            APP_KEY: base64:$$cap_app_key
            APP_DEBUG: 'false'
            APP_NAME: $$cap_app_name
            APP_URL: $$cap_app_url
            QUEUE_CONNECTION: redis
            SESSION_DRIVER: redis
            CACHE_DRIVER: redis
            LOG_CHANNEL: errorlog
            DB_CONNECTION: pgsql
            DATABASE_URL: postgres://$$cap_pg_user:$$cap_pg_pass@srv-captain--$$cap_appname-db/$$cap_pg_db
            __REDIS_CLIENT: predis
            REDIS_HOST: srv-captain--$$cap_appname-redis
            REDIS_PASSWORD: $$cap_redis_password
            __X_FORWARDED_PROTO: https
        depends_on:
            - $$cap_appname-db
            - $$cap_appname-redis
    $$cap_appname-db:
        image: postgres:$$cap_postgres_version
        volumes:
            - $$cap_appname-db-data:/var/lib/postgresql/data
        restart: always
        environment:
            POSTGRES_USER: $$cap_pg_user
            POSTGRES_PASSWORD: $$cap_pg_pass
            POSTGRES_DB: $$cap_pg_db
        caproverExtra:
            notExposeAsWebApp: 'true'
    $$cap_appname-redis:
        volumes:
            - $$cap_appname-redis-data:/data
        restart: always
        environment:
            REDIS_PASSWORD: $$cap_redis_password
        caproverExtra:
            dockerfileLines:
                - FROM redis:$$cap_redis_version
                - CMD exec redis-server --requirepass "$$cap_redis_password" --save "900 1 300 10 60 10000"
            notExposeAsWebApp: 'true'
caproverOneClickApp:
    variables:
        - id: $$cap_appname
          label: App name
          defaultValue: '{{ $domainDashes }}'
          description: Check out their Docker page for the valid tags https://hub.docker.com/r/library/postgres/tags/
          validRegex: /^([^\s^\/])+$/
        - id: $$cap_postgres_version
          label: Postgres Version
          defaultValue: '{{ $postgresVersion }}'
          description: Check out their Docker page for the valid tags https://hub.docker.com/r/library/postgres/tags/
          validRegex: /^([^\s^\/])+$/
        - id: $$cap_pg_user
          label: Postgres User
          defaultValue: 'webapp'
          validRegex: /.{1,}/
        - id: $$cap_pg_db
          label: Postgres Default Database
          defaultValue: 'webapp'
          validRegex: /.{1,}/
        - id: $$cap_pg_pass
          label: Database password
          description: ''
          validRegex: /.{1,}/
          defaultValue: ''
        - id: $$cap_redis_version
          label: Redis Version Tag
          description: 'Check out their Docker page for the valid tags: https://hub.docker.com/_/redis?tab=tags'
          defaultValue: '{{ $redisVersion }}'
          validRegex: /^([^\s^\/])+$/
        - id: $$cap_redis_password
          label: Redis Password
          validRegex: /^(\w|[^\s"])+$/
          defaultValue: ''
        - id: $$cap_app_name
          label: App Name
          defaultValue: '{{ $domain }}'
        - id: $$cap_app_url
          label: App URL
          defaultValue: 'http://{{ $domain }}'
        - id: $$cap_app_key
          label: App key
          description: 'Run: head /dev/urandom | head -c 32 | base64'
          defaultValue: ''
    instructions:
        start: >-
            Start
        end: >
            Done! You can now upload your Laravel app via:
            - cd {{ $domain }}
            - sed -i '' 's/CAPROVER_HOST_REPLACE_ME/yourhost/' package.json
            - git init; echo .idea >> .gitignore; git add .; git commit -m init
            - npm run deploy
    displayName: Laravel App
------ END HERE

--
To fix "Powered by CapRover" on deploy, change the app's nginx.conf from caprover UI:
location = /captain_502_custom_error_page.html {
# root <%-s.customErrorPagesDirectory%>;
return 502 "Please wait...";
internal;
}


TODO:

- check ip detected correctly via multiple nginxes
- https instructions
- test websockets?
- IS_MASTER for crons
- /app/restarting for deploys
- healthchecks
- mix manifest to gitignore

---

Minio storage
-------------

Run:
```
composer require league/flysystem-aws-s3-v3:"^1.0"
```

config/filesystems.php
```
'minio' => [
    'driver' => 's3',
    'endpoint' => env('MINIO_ENDPOINT'),
    'use_path_style_endpoint' => true,
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
],
```

.env / .env.example
```
FILESYSTEM_DRIVER=minio
MINIO_ENDPOINT="http://127.0.0.1:9000"
AWS_ACCESS_KEY_ID=testkey
AWS_SECRET_ACCESS_KEY=miniosecret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=test
```
(remove AWS_* below!)

package.json:
```
"start-minio": "docker run -p 9000:9000 -e \"MINIO_ACCESS_KEY=testkey\" -e \"MINIO_SECRET_KEY=miniosecret\"  minio/minio server --address :9000 /data",
```

Start minio, open, create the `test` bucket