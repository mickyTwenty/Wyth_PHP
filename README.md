# SeatUs (Wherr2) - AppMaister Project

## Installation

To install this package you will need
  - Laravel 5.4
  - PHP 5.5

The best way to install this package is with the help of composer. Clone this repo and run
```
composer install
```

It'll download all dependencies to project, then import database file and configure project `.env` file

## How to Start

1. Need to install docker && docker-compose(optional)

Read this article https://cwiki.apache.org/confluence/pages/viewpage.action?pageId=94798094

2. Copy .env.example as .env  ( optional for docker-compose)
Configure these keys in .env  
- UID (developer set their own uid )
- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

3. Install php packages  

You have to install php packages by running `composer install`.  

Under project directory, run `composer install`

4. Build docker images  

`docker build . -t laravel-app`

5. Run docker containers  

`docker run -p 80:80 laravel-app`

### Team
-- Ahsaan Muhammad Yousuf (Backend)

-- Rohail Ahmed Hashmi (Android)

-- Qazi Naveed (iOS)

-- Mohsin Lakhani (Project Manager)
 
### Backend Team
-- Ahsaan Muhammad Yousuf

-- Adnan Tariq
