## About Woven Advice Tutorial App

 ***compatible with Apple (Mac Silicon | Intel Silicon)***

 PHP 8.4 | Laravel 11.x | MySQL 8.0 | Docker Compose 3.8 
 
 Composer 2.8 | Node 22.21.1 | NPM 10.9.4

#### Clone Repository
````
git clone https://github.com/aungkokoye/woven-advice.git
cd woven-advice
cp src/.env.example src/.env
````
#### how to start docker
````
cd docker
docker compose up --build -d
````
#### how to stop docker
````
cd docker
docker compose down
````
#### Inside app web container
````
docker exec -it laravel_app bash
````
#### Set Up

***make sure run following commands inside the `laravel_app` container***
````
- cd /var/www/html
- composer install 
- supervisorctl start queue_worker
- npm install && npm run build // optional: for FE assets
- php artisan key:generate
````

#### PHPStorm IDE
DB settings
````
host: 127.0.0.1
port: 3506
user: root
password: root
database: woven
````
ID Plugin settings

***make sure run following commands inside the `laravel_app` container***
````
- cd /var/www/html
- composer require --dev barryvdh/laravel-ide-helper
- php artisan ide-helper:generate
- php artisan ide-helper:models --nowrite
````
#### Schickling MailCatcher Documentation:
For testing email functionality, we are using the Schickling MailCatcher.
It is accessible at `http://localhost:2080`.
This allows you to view emails sent by the application without needing a real email server.
MailCatcher is configured by setting the environment variable in your `.env` file:
#### RabbitMQ Information:

RabbitMQ is used for message queuing in the application.

It is accessible at `http://localhost:15672` with the following credentials:
```
Username: guest
Password: guest
```
#### Supervisord Information:

Supervisord is used to manage the background processes in the application.

Please check the `supervisord.conf` file for more information.

To manage the queue worker using Supervisor, open http://localhost:9001 with the following credentials:
```
Username: guest
Password: guest
```

### Learning Laravel
Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.
