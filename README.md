Тестовое задание по созданию простого ГИС API
=============================================

1. [Установка](#install)
  2. [Установка composer (опционально)](#install-composer)
  3. [Установка файлов проекта](#install-project)
  4. [Установка и первоначальная настройка БД](#install-db)
  5. [Создание рабочей схемы приложения](#config-db)
  6. [Загрузка тестовых данных](#load-fixtures)
  7. [Запуск приложения в режиме разарботчика, запуск юнит тестов](#test-run)
8. [API: РУКОВОДСТВО](#api-doc)
  9. [Базовая структура ответа на запрос к API](#api-base) 
  10. [Рубрики](#rubrics)
  11. [Здания](#buildings)
  12. [Организации](#organizations)
13. [Рабочий сервер проекта](#working-project)
  14. [On-line документация](#online-doc)
15. [TODO](#todo)

<div id='install'/>
УСТАНОВКА
---------

<div id='install-composer'/>
### Установка composer (опционально)

Для установки composer на Linux и Mac воспользуйтесь следующими командами:

~~~
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
~~~

Для установки composer'а на других системах следуйте инструкциям на сайте [getcomposer.org](https://getcomposer.org/download/)

<div id='install-project'/>
### Установка файлов проекта

Скачиваем с github код проекта

~~~
git clone https://github.com/lanofears/simple-gis.git $project_folder$
~~~

Устанавливаем с помощью composer зависимости

~~~
composer update
~~~

<div id='install-db'/>
### Установка и первоначальная настройка БД

Процесс установки PostgreSQL и расширения PostGIS зависит от используемой операционной системы.
Можно скачать программу установки PostgreSQL с [www.enterprisedb.com](http://www.enterprisedb.com/products-services-training/pgdownload#windows), или установить из реопзитория (Linux).
Процесс установки расширения PostGIS подробно описан на сайте проекта [postgis.net](http://postgis.net/install/).

Чтобы создать пользователя БД (необходимого для работы приложения) и рабочую схему, нужно выполнить SQL скрипт c помощью консольной утилиты psql:   

```sql
CREATE DATABASE simple_gis;
CREATE USER webserver WITH password 'test';
GRANT ALL privileges ON DATABASE simple_gis TO webserver;

\connect simple_gis;
CREATE EXTENSION postgis;
\q
```

<div id='config-db'/>
### Создание рабочей схемы приложения

~~~
php bin/console --no-interaction doctrine:migrations:migrate
~~~

<div id='load-fixtures'/>
### Загрузка тестовых данных

~~~
php bin/console --no-interaction hautelook_alice:doctrine:fixtures:load 
~~~

<div id='test-run'/>
### Запуск приложения в режиме разарботчика, запуск юнит тестов

Запуск юнит тестов работы ORM:
~~~
vendor/bin/phpunit tests/AppBundle/Entity/ 
~~~

Запуск юнит тестов работы контроллеров API:
~~~
vendor/bin/phpunit tests/AppBundle/Controller/ 
~~~

Запуск приложения в режиме разработчика:
~~~
php bin/console server:run 
~~~

<div id='api-doc'/>
API: РУКОВОДСТВО
----------------

<div id='api-base'/>
## Базовая структура ответа на запрос к API

Базовая структура ответа на запрос:
 
| Параметр       | Тип              | Описание                         |
| -------------- |:----------------:| -------------------------------- |
| success        | boolean          | Признак успешности выполнения запроса. Если во время запроса произошла ошибка - false, если запрос выполнен успешно - true |
| code           | integer          | HTTP код статуса выполнения запроса |
| total          | integer          | Опционально. Общее кол-во найденных объектов, выводится только для запросов у которых предусмотрена постраничная выдача результата |
| блок с данными | ``array|string`` | Наименование блока, содержащего данные запроса, зависит от ресурса и от успешности выполненния запроса |

Возможные значения блока данных запроса:

- rubrics: Данные о рубрике/рубриках - см. раздел [Рубрики](#rubrics) 
- buildings: Данные о здание/зданиях - см. раздел [Здания](#buildings) 
- organizations: Данные о организации/организациях - см. раздел [Организации](#organizations) 
- message: Текст ошибки, выводится в случае неуспешного выполнения запроса 

<div id='rubrics'/>
## Рубрики

**/api/rubric**

Рубрики представлены в виде древовидной структуры. У каждой рубрики есть родительский элемент (кроме основных рубрик, которые не имеют родительского элемента) и список дочерних рубрик.
Параметры рубрики:

| Параметр       | Тип           | Описание                         |
| -------------- |:-------------:| -------------------------------- |
| id             | integer       | Уникальный идентификатор рубрики |
| parent_id      | integer       | Идентификатор родительской рубрики. У корневых рубрик равен null |
| name           | string        | Наименование рубрики |
| children       | array         | Опционально. Список дочерних рубрик |

### Вывод подробной информации о рубрике

**/api/rubric/{id}**

Параметры запроса:

| Параметр       | Тип           | Описание                         |
| -------------- |:-------------:| -------------------------------- |
| id             | integer       | Уникальный идентификатор рубрики |

Пример ответа на запрос:

```javascript
{
    "success" : true,
    "code" : 200,
    "rubrics" : [
        {
            "id" : 23,
            "parent_id_" : null,
            "name" : "Еда",
            "children" : [
                {
                    "id" : 25,
                    "name" : "Полуфабрикаты"
                }
            ]
        },
        ...
    ]
}
```

### Поиск по рубрикам

**/api/rubric/?name=часть_наименования_рубрики&parent=идентификатор_родительской_рубрики&order=сортировка&limit=кол_во_в_блоке&page=номер_блока**

| Параметр       | Тип           | Формат          | Описание                         |
| -------------- |:-------------:| --------------- | -------------------------------- |
| name           | string        |                 | Фильтр по наименованию рубрики. Можно указать часть наименования. |
| parent         | integer       |                 | Фильтр для вывода всех дочерних рубрик, для указанной рубрики. Указывается идентификатор рубрики.  |
| order          | string        | ``name|parent`` | Сортировка списка, может принимать значения: name (по умолчанию) - сортировка по наименованию, parent - сортировка по идентификатору родительской категории. |
| limit          | integer       |                 | Ограничение на кол-во элементов в блоке постраничной выдачи данных. По умолчанию - 100, минимальное значение - 1, максимальное значение 1000  |
| page           | integer       |                 | Номер блока постраничной выдачи данных. По умолчанию - 1, минимальное значение - 1 |

Пример ответа на запрос:

```javascript
{
    "success" : true,
    "code" : 200,
    "total" : 124
    "rubrics" : [
        {
            "id" : 23,
            "parent_id_" : null,
            "name" : "Еда"
        },
        {
            "id" : 25,
            "parent_id_" : null,
            "name" : "Автомобили"
        },
        ...
    ]
}
```

<div id='buildings'/>
## Здания

**/api/building**

Ресурс позволяет производить поиск зданий по части адреса, или по нахождению в определенном радиусе от заданной точки.
Параметры здания:

| Параметр       | Тип           | Описание                         |
| -------------- |:-------------:| -------------------------------- |
| id             | integer       | Уникальный идентификатор здания |
| address        | string        | Адрес здания |
| latitude       | float         | Широта координат расположения здания |
| longitude      | float         | Долгота координат расположения здания |
| organizations  | array         | Опционально. Список всех организаций в здании - см. раздел [Организации](#organizations). Выводится только для подробной информации о здании |

### Вывод подробной информации о здании

**/api/building/{id}**

Параметры запроса:

| Параметр       | Тип           | Описание                         |
| -------------- |:-------------:| -------------------------------- |
| id             | integer       | Уникальный идентификатор здания  |

Пример ответа на запрос:

```javascript
{
    "success" : true,
    "code" : 200,
    "buildings" : [
        {
            "id":62035,
            "address":"Новосибирская область, город Новосибирск, улица Советская, 8",
            "longitude" : 82.91844213,
            "latitude" : 55.02367856,
            "organizations":[
                {
                    "id":31254,
                    "name":"ЗАО Авто 2620368",
                    "phones":[
                        "(812) 842-86-85",
                        "(35222) 25-7203"
                    ],
                    "rubrics":[
                    {
                        "id":3192,
                        "parent_id":3103,
                        "name":"Шины и диски"
                    },
                    {
                        "id":3263,
                        "parent_id":3116,
                        "name":"Легковые автомобили"
                    }
                }
            ]
        }
    ]
}
```

### Поиск по каталогу зданий

Поиск зданий по заданным критериям: address - часть строки адреса здания, location - поиск зданий в заданном радиусе от указанной точки.
Значения сортируются по адресу.

**/api/building/?address=часть_адреса&location=широта,долгота,радиус&limit=кол_во_в_блоке&page=номер_блока**

| Параметр       | Тип                   | Формат                    | Описание                         |
| -------------- |:---------------------:| ------------------------- | -------------------------------- |
| address        | string                |                           | Фильтр по наименованию рубрики. Можно указать часть наименования. |
| location       | float, float, integer | latitude,longitude,radius | Фильтр для вывода всех дочерних рубрик, для указанной рубрики. Указывается идентификатор рубрики.  |
| limit          | integer               |                           | Ограничение на кол-во элементов в блоке постраничной выдачи данных. По умолчанию - 100, минимальное значение - 1, максимальное значение 1000  |
| page           | integer               |                           | Номер блока постраничной выдачи данных. По умолчанию - 1, минимальное значение - 1 |

Пример ответа на запрос:

```javascript
{
    "success" : true,
    "code" : 200,
    "total" : 100,
    "buildings" : [
        {
            "id" : 61108,
            "address" : "028996, Ивановская область, город Егорьевск, наб. Косиора, 98",
            "longitude" : 83.00177557,
            "latitude" : 55.01901161
        },
        {
            "id" : 59609,
            "address" : "030144, Курская область, город Мытищи, въезд Чехова, 84",
            "longitude" : 83.31952263,
            "latitude" : 54.92805301
        },
        ...
    ]
}
```

<div id='organizations'/>
## Организации

**/api/organization**

Параметры организации:

| Параметр       | Тип           | Описание                         |
| -------------- |:-------------:| -------------------------------- |
| id             | integer       | Уникальный идентификатор здания |
| building       | array         | Информация о здании в котором находится организация - см. раздел [Здания](#buildings) |
| name           | string        | Наименование организации |
| phones         | array         | Список контактных телефонов организации |
| rubrics        | array         | Список всех рубрик в которые входит организация - см. раздел [Рубрики](#rubrics) |

Ресурс позволяет производить поиск организаций по адресу их расположения, или по нахождению в определенном радиусе от заданной точки, а так же по рубрике (точное совпадение, или рекурсивный поиск по дочерним рубрикам)

### Вывод подробной информации об организации

**/api/organization/{id}**

Параметры запроса:

| Параметр       | Тип           | Описание                              |
| -------------- |:-------------:| ------------------------------------- |
| id             | integer       | Уникальный идентификатор организации  |

Пример ответа на запрос:

```javascript
{
    "success":true,
    "code":200,
    "organizations" : [
        {
            "id" : 25492,
            "name" : "ОАО ТрансТрансСтройПром 1218532",
            "phones" : [
                "(495) 208-5701"
            ],
            "building":{
                "id":61678,
                "address":"729792, Новосибирская область, город Мытищи, спуск Балканская, 47",
                "longitude":83.60538753,
                "latitude":59.22560763
            },
            "rubrics" : [
                {
                    "id":3192,
                    "parent_id":3103,
                    "name":"Шины и диски"
                },
                {
                    "id":3263,
                    "parent_id":3116,
                    "name":"Легковые автомобили"
                }
            ]
        },
        ...
    ]
}
```

### Поиск каталогу организаций

Поиск по каталогу организаций с возможностью поиска по различным критериям

- Поиск по адресу - можно искать по части адреса. Если указано несколько частей, то необходимо соблюдать последовательность их нахождения в адресе.    
- Поиск по местоположению - можно искать организации находящиеся в определенном радиусе от указанной точки.
- Поиск по рубрике - можно искать организации с определенной рубрикой. Поиск работает в двух режимах: точное совпадение - выбираются только организации в которых конкретно указанна заданная рубрика, рекурсивно - ищутся не только организации в определенной рубрике, но и во всех ее дочерних рубриках  

**/api/building/?address=часть_адреса&location=широта,долгота,радиус&order=сортировка&limit=кол_во_в_блоке&page=номер_блока**

| Параметр       | Тип                   | Формат                    | Описание                         |
| -------------- |:---------------------:| ------------------------- | -------------------------------- |
| name           | string                |                           | Фильтр по наименованию организации. Можно указать часть наименования. |
| address        | string                |                           | Фильтр по наименованию рубрики. Можно указать часть наименования. |
| location       | float, float, integer | latitude,longitude,radius | Фильтр для вывода всех дочерних рубрик, для указанной рубрики. Указывается идентификатор рубрики.  |
| rubric         | string                | rubric_id[,recursive]     | Фильтр по наименованию рубрики. Можно указать часть наименования. |
| order          | string                | ``name|address``          | Сортировка списка, может принимать значения: name (по умолчанию) - сортировка по наименованию, parent - сортировка по идентификатору родительской категории. |
| limit          | integer               |                           | Ограничение на кол-во элементов в блоке постраничной выдачи данных. По умолчанию - 100, минимальное значение - 1, максимальное значение 1000  |
| page           | integer               |                           | Номер блока постраничной выдачи данных. По умолчанию - 1, минимальное значение - 1 |

Пример ответа на запрос:

```javascript
{
    "success":true,
    "code":200,
    "total" : 100,
    "organizations" : [
        {
            "id":31254,
            "name":"ЗАО Авто 2620368",
            "phones":[
                "(812) 842-86-85",
                "(35222) 25-7203"
            ],
            "building":{
                "id":61678,
                "address":"729792, Новосибирская область, город Мытищи, спуск Балканская, 47",
                "longitude":83.60538753,
                "latitude":59.22560763
            },
            "rubrics":[
                {
                    "id":3192,
                    "parent_id":3103,
                    "name":"Шины и диски"
                },
                {
                    "id":3263,
                    "parent_id":3116,
                    "name":"Легковые автомобили"
                }
            ]
        },
        ...
    ]
}
```

<div id='working-project'/>
Рабочий сервер проекта
----------------------

Рабочая версия проекта размещена на облачном хостинге Amazon:

 - Рубрики: http://ec2-52-37-22-127.us-west-2.compute.amazonaws.com/api/rubric/
 - Здания: http://ec2-52-37-22-127.us-west-2.compute.amazonaws.com/api/building/
 - Организации: http://ec2-52-37-22-127.us-west-2.compute.amazonaws.com/api/organization/

<div id='online-doc'/>
### On-line документация

On-line документация API находится по адресу /api/doc
При нажатии на краткое описание метода ресурса можно посмотреть подробную информацию и параметрах, а также отправить тестовый запрос.

 - Документация API: http://ec2-52-37-22-127.us-west-2.compute.amazonaws.com/api/doc/
 - Документация для отдельного метода API: http://ec2-52-37-22-127.us-west-2.compute.amazonaws.com/api/...метод/?_doc
  

<div id='todo'/>
TODO:
-----

- Использование полей с типом Point для хранения координат здания, добавление индекса по данному полю.
- Использования Nested set для работы с рубриками, для ускорения нахождения всех предков заданной рубрики.
- Добавить поддержку формата данных - XML
- Добавление поддержки версионности API
