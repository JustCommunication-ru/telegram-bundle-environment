# CacheBundle

## Установка 
`composer require justcommunication/cache-bundle`

## Подключение
Прописать в /config/services.yaml:
```
parameters:
    ...    
    memcache:
        host: 127.0.0.1
        port: 11211
        namespace: 'tb'
        lifetime: 86400
```