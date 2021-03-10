# Minimalistic CORS library [![Build Status](https://dev.azure.com/keboola-dev/cors/_apis/build/status/keboola.cors?branchName=main)](https://dev.azure.com/keboola-dev/cors/_build/latest?definitionId=67&branchName=main)

Adds CORS headers and OPTIONS response to all API endpoints

## Usage
```bash
composer require keboola/minimalist-cors
```

Add this to the `services.yaml` file:

```yaml
    Keboola\Cors\CorsListener:
        arguments:
            -  
        tags:
            - { name: kernel.event_listener, event: kernel.request }
            - { name: kernel.event_listener, event: kernel.response }

    Keboola\Cors\ResponseHeadersListener:
        tags:
            - { name: kernel.event_listener, event: kernel.response }
```


## Development
Run the tests:

```bash
docker-compose build
docker-compose run tests
```

To run tests with local code use:

```bash
docker-compose run tests-local composer install
docker-compose run tests-local
```
