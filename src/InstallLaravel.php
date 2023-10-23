<?php

namespace CaproverLaravel;

use RuntimeException;

class InstallLaravel
{
    private $projectName;
    private $domain;

    public function __construct($projectName, $domain)
    {
        $this->projectName = $projectName;
        $this->domain = $domain;
    }

    public function run()
    {
        $postgresVersion = '16';
        $postgresPort = mt_rand(5234, 5234+1000);
        $testDbPostgresPort = $postgresPort + 1;
        $redisVersion = '7.2';
        $redisPort = mt_rand(6379, 6379+1000);
        $testRedisPort = $redisPort + 1;
        $elasticVersion = '8.10.4';
        $elasticPort = mt_rand(9200, 9200+1000);
        $testElasticPort = $elasticPort + 1;

        $this->createDockerCompose(get_defined_vars());
        $this->createDockerignore();
        $this->updateEnv($postgresPort, $redisPort);
        $this->updatePhpUnitXml($testDbPostgresPort, $testRedisPort);
        $this->updatePackageJson();
        $this->updateTrustProxies();
        $this->updateTestCase();
        $this->createCaproverDeployFile(get_defined_vars());
        $this->miscFiles();
        $this->installIdeHelpers();

    }

    function fileReplaceBetween($filename, $start, $end, $replace)
    {
        $text = file_get_contents($filename);
        $startI = strpos($text, $start);
        if ($startI === false) {
            throw new RuntimeException("$startI not found in $filename");
        }
        $endI = strpos($text, $end);
        if ($endI === false) {
            throw new RuntimeException("$endI not found in $filename");
        }
        $newContent = substr($text, 0, $startI) . $replace . substr($text, $endI, strlen($text) - $endI);
        file_put_contents($filename, $newContent);
    }

    function fileReplace($filename, $search, $replace)
    {
        $text = file_get_contents($filename);
        $newContent = str_replace($search, $replace, $text);
        file_put_contents($filename, $newContent);
    }

    function fileInsertAfter($filename, $start, $replace)
    {
        $text = file_get_contents($filename);
        $startI = strpos($text, $start);
        if ($startI === false) {
            throw new RuntimeException("$startI not found in $filename");
        }
        $startI += strlen($start);
        $endI = $startI;
        $newContent = substr($text, 0, $startI) . $replace . substr($text, $endI, strlen($text) - $endI);
        file_put_contents($filename, $newContent);
    }

    public function updateTestCase()
    {
        file_put_contents($this->projectName . '/tests/TestCase.php', $this->testCaseFile());
    }

    public function updatePackageJson()
    {
        $this->fileInsertAfter($this->projectName . '/package.json', "\"scripts\": {", $this->packageJsonFile());
    }

    public function updatePhpUnitXml($testDbPostgresPort, $testRedisPort)
    {
        $connection = "postgres://webapp:secret@localhost:$testDbPostgresPort/webapp?sslmode=disable";
        $replace = "\n        <server name=\"DATABASE_URL\" value=\"{$connection}\"/>\n" .
                     "        <server name=\"DB_CONNECTION\" value=\"pgsql\"/>\n" .
                     "        <server name=\"REDIS_PORT\" value=\"{$testRedisPort}\"/>\n"
        ;
        $this->fileInsertAfter($this->projectName . '/phpunit.xml', '<php>',
            $replace);
    }

    public function updateEnv($postgresPort, $redisPort)
    {
        $exampleFile = $this->projectName . '/.env.example';
        $envFile = $this->projectName . '/.env';
        foreach([$envFile, $exampleFile] as $file) {
            $dbFragment = "DB_CONNECTION=pgsql\nDATABASE_URL=postgres://webapp:secret@localhost:$postgresPort/webapp?sslmode=disable";
            $this->fileReplaceBetween($file, 'DB_CONNECTION', 'DB_PASSWORD=', "{$dbFragment}\n");
            $this->fileReplace($file, "DB_PASSWORD=\n", "");
            $this->fileReplace($file, "REDIS_HOST=redis", "REDIS_HOST=localhost");
            $this->fileReplace($file, "REDIS_PORT=6379", "REDIS_PORT=$redisPort");
        }
    }

    public function createDockerCompose($vars)
    {
        $dockerCompose = $this->dockerComposeFile($vars);
        file_put_contents($this->projectName . '/docker-compose.yml', $dockerCompose);
    }

    private function installIdeHelpers()
    {
        system('cd ' . $this->projectName . ' && composer require barryvdh/laravel-ide-helper');
//        $start = "         * Package Service Providers...\n         */\n";
//        $replace = "        \\Barryvdh\\LaravelIdeHelper\\IdeHelperServiceProvider::class,\n        ";
//        $this->fileInsertAfter($this->projectName . '/config/app.php', $start, $replace);
        $this->fileInsertAfter($this->projectName . '/composer.json',
            '"Illuminate\\\\Foundation\\\\ComposerScripts::postAutoloadDump",',
            "\n            \"php artisan ide-helper:generate\",
            \"php artisan ide-helper:meta\",");
        system('cd ' . $this->projectName . ' && (php artisan ide-helper:generate; php artisan ide-helper:meta)');
    }

    public function blade($fn, $replaces)
    {
        # we don't use actual Blade, because it meses up whitespace needed for docker-compose.yml
        $file = __DIR__ . '/../files/' . $fn . '.blade.php';
        $text = file_get_contents($file);
        foreach($replaces as $replace => $value) {
            $text = str_replace("{{ \${$replace} }}", $value, $text);
        }
        return $text;
    }

    public function dockerComposeFile($vars)
    {
        return $this->blade('docker-compose-yml', $vars);
    }

    public function testCaseFile()
    {
        return file_get_contents(__DIR__ . '/../files/TestCase');
    }

    public function packageJsonFile()
    {
        $domainDashes = preg_replace('#[^A-Za-z0-9]#', '-', $this->domain);
        return str_replace('{DOMAIN_DASH}', $domainDashes, '
        "serve": "php artisan serve",
        "deploy": "caprover deploy -n CAPROVER_HOST_REPLACE_ME -a {DOMAIN_DASH} -b master",
        "queue": "php artisan queue:work --tries=1",
        "start-compose": "docker-compose up",
        "stop-compose": "docker-compose stop",
        "migrate": "php artisan migrate",
        "migrate:rollback": "php artisan migrate:rollback",');
    }

    public function createCaproverDeployFile($vars)
    {
        file_put_contents($this->projectName . '/caprover-deploy.txt', $this->deployCaproverFile($vars));
    }

    public function deployCaproverFile($vars)
    {
        $domainDashes = preg_replace('#[^A-Za-z0-9]#', '-', $this->domain);
        $domain = $this->domain;
        return $this->blade('deploy-caprover-txt', array_merge($vars, ['domain' => $domain, 'domainDashes' => $domainDashes]));
    }

    public function miscFiles()
    {
        mkdir($this->projectName . '/resources/docker/');
        $files = ['cron.sh', 'entrypoint.sh', 'nginx.conf.tpl', 'php.ini', 'php-fpm.conf.tpl', 'queue.sh', 'supervisor.conf'];
        foreach ($files as $file) {
            copy(__DIR__ . '/../files/docker/' . $file, $this->projectName . '/resources/docker/' . $file);
        }
        chmod($this->projectName . '/resources/docker/queue.sh', 0755);
        chmod($this->projectName . '/resources/docker/cron.sh', 0755);

        copy(__DIR__ . '/../files/Dockerfile', $this->projectName . '/Dockerfile');
    }

    private function updateTrustProxies()
    {
        $this->fileInsertAfter($this->projectName . '/app/Http/Middleware/TrustProxies.php',
            'protected $proxies',
            "= '*'");
    }

    private function createDockerignore()
    {
        copy(__DIR__ . '/../files/.dockerignore', $this->projectName . '/.dockerignore');
    }
}
