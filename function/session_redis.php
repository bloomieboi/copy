<?php
/**
 * Redis: кэширование и сессии.
 * Для использования Redis как хранилища сессий нужен PHP-расширение redis
 * и настройка до session_start():
 *   ini_set('session.save_handler', 'redis');
 *   ini_set('session.save_path', 'tcp://127.0.0.1:6379');
 * Библиотека Predis (composer require predis/predis) подходит для кэша
 * в коде (например, кэш каталога услуг), но не подменяет session.save_handler.
 */
