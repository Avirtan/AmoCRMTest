# PhpTemplateDocker

Для запуска 
1) Перейти в backend и выполнить composer install
2) на уровне проекта выполнить make файл или скопировать содержимое оттуда, и выполнить в консоле
3) перейти на localhost:80 и проверить, что всё работает 

Для запуска Xdebug в PhpStorm
1) File -> Settings -> Servers (установить как на фото ниже)
Пояснения
1) Name должно быть такое же название как в Dockerfile php(в строке(32) ENV PHP_IDE_CONFIG 'serverName=ТУТ_ДОЛЖЕН_БЫТЬ_ВАШ_NAME')
2) Host должен быть такой же как в конфиге nginx, в данном случае стоит localhost
3) Указать путь к папке как она лежит на сервере, в данном случае папка(backend) скопируется в путь /var/www
![image](https://github.com/Avirtan/PhpTemplateDocker/assets/36964697/2f7df628-8123-49ed-a3be-c3ec55be48b6)

Отличия для разных ОС
В файле php/xdebug.ini 
Для винды нужно указать 
xdebug.client_host=host.docker.internal
Для линуха
xdebug.client_host=172.17.0.1 (но это не точно, возможно придётся гуглить)

2) Включение Xdebug в phpStorm перейти Run->Edit Configuration, там добавить Php Remote Debug(сделать как на скрине ниже)
Пояснение
1) поставить галочку у Filter debug....
2) выбрать сервер который создали в 1 пункте
3) установить IDE KEY, эта строка указывается в конфиге корень проекта ./php/xdebug.ini, они должны совпадать, как там так и тут
![image](https://github.com/Avirtan/PhpTemplateDocker/assets/36964697/58b5d482-a988-4c3e-88ab-765d8e6ab5aa)

Полезные ссылки 
https://handynotes.ru/2020/12/phpstorm-php-8-docker-xdebug-3.html
https://habr.com/ru/articles/712670/ (в комментах есть ссылка на видео)
