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

1. Need to install docker && docker-compose

Read this article https://cwiki.apache.org/confluence/pages/viewpage.action?pageId=94798094

2. Copy .env.example as .env
Configure these keys in .env 
UID (developer set their own uid )
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD

3. Build docker images
docker-compose build

4. Run docker containers
docker-compose up -d && docker-compose logs -f

### Team
-- Ahsaan Muhammad Yousuf (Backend)

-- Rohail Ahmed Hashmi (Android)

-- Qazi Naveed (iOS)

-- Mohsin Lakhani (Project Manager)
 
### Backend Team
-- Ahsaan Muhammad Yousuf

-- Adnan Tariq
