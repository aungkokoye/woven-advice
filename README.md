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
- php artisan migrate
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
#### API Documentation

- For Authentication, Laravel Sanctum should use.

- Default expire time for token can change it in `config/sanctum.php` file.

- For Authorization, Laravel Policy should use.

- For Rate Limiting, Laravel Throttle should use, default requests per minute can change it in `.env` file.

CSV File Upload api endpoint:

-  upload (POST)  : Saved the uploaded csv file to src/storage/app/private/csv-investor folder with current timestamp as file name.
- Process the uploaded csv file in the background using Laravel Job and Queue.
```
url: 127.0.0.1:8275/api/csv-upload
method: POST
body: form-data
key: file
value: (select a csv file)

CURL Example: curl -X POST http://127.0.0.1:8275/api/csv-upload -F "file=@<file-path>/<file-name>.csv"
```

Investor api endpoints:
- averageAge (GET) : Get average age of all investors.
```
url: 127.0.0.1:8275/api/investor/avg-age
method: GET
```

- averageInvestment (GET) : Get average investment amount of all investors.
```
url: 127.0.0.1:8275/api/investor/avg-investment
method: GET
```

- totalInvestments (GET) : Get total investment number of all investors.
```
url: 127.0.0.1:8275/api/investor/total-investments
method: GET
```

- listInvestors (GET) : Get all investors with his/her total investment amount. (should use pagination for large data)
```
url: 127.0.0.1:8275/api/investors
method: GET
```

#### Testing:

How to run the tests (unit and feature tests) inside the `laravel_app` container:

Unit tests cover following class
- CsvInvestorImportService
- InvestorService
- CsvInvestorImportJob

***make sure run following commands inside the `laravel_app` container***
````
- cd /var/www/html
- php artisan test  
````

### Learning Laravel
Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.
