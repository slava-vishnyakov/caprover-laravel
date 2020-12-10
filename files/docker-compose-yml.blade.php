version: '2'

services:

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

  redis_text:
    image: redis:{{ $redisVersion }}
    ports:
      - "{{ $testRedisPort }}:6379"

#  elastic:
#    image: docker.elastic.co/elasticsearch/elasticsearch:{{ $elasticVersion }}
#    ports:
#      - "{{ $elasticPort }}:9200"
#
#  elastic_test:
#    image: docker.elastic.co/elasticsearch/elasticsearch:{{ $elasticVersion }}
#    ports:
#      - "{{ $testElasticPort }}:9200"
