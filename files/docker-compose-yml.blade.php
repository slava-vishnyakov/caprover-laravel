version: '2'

services:
  php-nginx:
    build:
      context: .
      dockerfile: Dockerfile.php-nginx
    ports:
      - "8081:80"
      - "5173:5173"
    volumes:
      - ".:/app"

  postgres:
    # psql --user=webapp --port={{ $postgresPort }} --password --host=0.0.0.0
    # DATABASE_URL=postgres://webapp:secret@localhost:{{ $postgresPort }}/webapp?sslmode=disable
    image: postgres:{{ $postgresVersion }}
    ports:
      - "{{ $postgresPort }}:5432"
    # volumes:
    #   - "./db/:/var/lib/postgresql/data"
    environment:
      POSTGRES_PASSWORD: secret
      POSTGRES_USER: webapp
      POSTGRES_DB: webapp

  postgres_test:
    # psql --user=webapp --port={{ $testDbPostgresPort }} --password --host=0.0.0.0
    # DATABASE_URL=postgres://webapp:secret@localhost:{{ $testDbPostgresPort }}/webapp?sslmode=disable
    image: postgres:{{ $postgresVersion }}
    ports:
      - "{{ $testDbPostgresPort }}:5432"
    environment:
      POSTGRES_PASSWORD: secret
      POSTGRES_USER: webapp
      POSTGRES_DB: webapp

  redis:
    image: redis:{{ $redisVersion }}
    ports:
      - "{{ $redisPort }}:6379"

  redis_test:
    image: redis:{{ $redisVersion }}
    ports:
      - "{{ $testRedisPort }}:6379"

# ELASTIC
# -------
#
# To install:
#
# 1. Uncomment the "elastic:" block below
#
# 2. composer require elasticsearch/elasticsearch
#
# 3. Add this to .env:
# ELASTICSEARCH_HOST=127.0.0.1
# ELASTICSEARCH_PASSWORD=secret
# ELASTICSEARCH_PORT=9412
#
# 4. Connect as
# Elastic\Elasticsearch\ClientBuilder::create()
# ->setHosts(['host' => env('ELASTICSEARCH_HOST'), 'port' => 9412, 'user' => 'elastic', 'password' => env('')])
# ->build();
#
#  elastic:
#    image: docker.elastic.co/elasticsearch/elasticsearch:{{ $elasticVersion }}
#    environment: ['ES_JAVA_OPTS=-Xms512m -Xmx512m','bootstrap.memory_lock=true','discovery.type=single-node','xpack.security.enabled=false', 'xpack.security.enrollment.enabled=false']
#    ports:
#      - "{{ $elasticPort }}:9200"
#
#  elastic_test:
#    image: docker.elastic.co/elasticsearch/elasticsearch:{{ $elasticVersion }}
#    environment: ['ES_JAVA_OPTS=-Xms512m -Xmx512m','bootstrap.memory_lock=true','discovery.type=single-node','xpack.security.enabled=false', 'xpack.security.enrollment.enabled=false']
#    ports:
#      - "{{ $testElasticPort }}:9200"
#
# 5. Add to phpunit.xml:
# <env name="ELASTICSEARCH_PORT" value="{{ $testElasticPort }}"/>
