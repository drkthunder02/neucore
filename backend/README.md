# Backend

The backend is based on the [Slim Framework](https://www.slimframework.com)
with the [PHP-DI container](http://php-di.org/).

## Style Guide

[PSR-2: Coding Style Guide](https://www.php-fig.org/psr/psr-2/)

## Commands

### Unit Tests

Run tests:
```
vendor/bin/phpunit
```

### Doctrine

Generate constructor, getters and setters
```
vendor/bin/doctrine orm:generate-entities src/classes
```

Generate repository classes
```
vendor/bin/doctrine orm:generate-repositories src/classes
```

Apply migrations
```
vendor/bin/doctrine-migrations migrations:migrate
```

Generate migration by comparing the current database to the mapping information
```
vendor/bin/doctrine-migrations migrations:diff
```

### Swagger

Generate swagger.json
```
vendor/bin/swagger --exclude bin,config,docs,var,vendor --output ../web
```

## App Auth

Authentication for apps is done via an HTTP Authorization header.

The authorization string is composed of the word Bearer followed by a base64-encoded
string containing the app ID and secret separated by a colon (1:my awesome secret).

Example:
```
curl --header "Authorization: Bearer MTpteSBhd2Vzb21lIHNlY3JldA==" https://brave.core.tld/api/app/info
```