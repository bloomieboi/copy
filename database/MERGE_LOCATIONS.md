# Объединение адресов и точек обслуживания

## Обзор изменений

**Дата:** 2026-02-10  
**Причина:** Адреса и точки обслуживания - это одна и та же сущность. Дублирование функционала создавало путаницу.

## Что было

### Старая структура (ДО)

```
Таблицы:
- address (address_id, address_name)
- locations (location_id, location_name, address, phone, is_active)

Файлы:
- admin/addresses.php - управление адресами
- admin/locations.php - управление точками обслуживания

Проблемы:
❌ Дублирование функционала
❌ Две таблицы для одного и того же
❌ Путаница для пользователей
❌ Сложность поддержки
```

## Что стало

### Новая структура (ПОСЛЕ)

```
Таблица:
- locations (location_id, location_name, address, phone, working_hours, is_active)
- VIEW address (для обратной совместимости)

Файлы:
- admin/locations.php - управление точками обслуживания (адресами)
- admin/addresses.php - переадресация на locations.php

Преимущества:
✅ Единая точка управления
✅ Меньше дублирования кода
✅ Проще для пользователей
✅ Легче поддержка
```

---

## Миграция базы данных

### SQL-скрипт: `database/merge_address_locations.sql`

**Шаги миграции:**

1. ✅ Добавление полей в `locations`:
   - `phone` VARCHAR(20)
   - `is_active` TINYINT(1)
   - `working_hours` VARCHAR(100)

2. ✅ Перенос данных из `address` в `locations`

3. ✅ Обновление `order_address`:
   - Добавление `location_id`
   - Маппинг `address_id` → `location_id`

4. ✅ Переименование `address` → `address_deprecated`

5. ✅ Создание VIEW `address` для обратной совместимости

### Выполнение миграции

```sql
-- В phpMyAdmin или MySQL клиенте:
USE copypasteDB;
SOURCE database/merge_address_locations.sql;

-- Или скопируйте содержимое файла и выполните
```

---

## Изменения в коде

### 1. admin/locations.php

**Обновлено:**
- ✅ Современный Bootstrap 5 интерфейс
- ✅ Добавлено поле "Часы работы"
- ✅ Валидация полей
- ✅ Активация/деактивация точек
- ✅ Счетчики активных/неактивных точек
- ✅ Улучшенная таблица с иконками

**Новые поля формы:**
```php
- Название точки обслуживания (обязательно, макс 100 символов)
- Адрес (обязательно, макс 255 символов)
- Телефон (необязательно, макс 20 символов)
- Часы работы (необязательно, макс 100 символов)
- Активна (checkbox)
```

### 2. admin/addresses.php

**Изменено:**
```php
<?php
// Переадресация на locations.php
header("Location: locations.php");
exit;
```

### 3. admin/index.php

**Было:**
```
Точки обслуживания
  - Все точки обслуживания

Настройки
  - Адреса
  - Услуги и прайс-лист
```

**Стало:**
```
Точки обслуживания и адреса
  - Управление точками обслуживания

Настройки
  - Услуги и прайс-лист
```

### 4. orders/create.php

**Было:**
```php
$addresses = $pdo->query("SELECT * FROM address ORDER BY address_name")->fetchAll();
```

**Стало:**
```php
$addresses = $pdo->query("
    SELECT 
        location_id as address_id, 
        CONCAT(location_name, ' - ', address) as address_name 
    FROM locations 
    WHERE is_active = 1 
    ORDER BY location_name
")->fetchAll();
```

**Результат в форме:**
```
Выберите адрес организации:
[ ] Копицентр на Ленина - ул. Ленина, 1
[ ] Копицентр на Пушкина - пр. Пушкина, 25
[ ] Филиал Центральный - ул. Мира, 10
```

---

## VIEW для обратной совместимости

### address (VIEW)

```sql
CREATE OR REPLACE VIEW address AS
SELECT 
    location_id as address_id,
    location_name as address_name
FROM locations
WHERE is_active = 1;
```

**Использование:**
```sql
-- Старый код продолжает работать
SELECT * FROM address;

-- Но лучше использовать locations напрямую
SELECT * FROM locations WHERE is_active = 1;
```

---

## UI/UX улучшения

### Современный интерфейс

**Карточки:**
```html
<section class="card mb-4">
    <div class="card-header">
        <h5>
            <i class="bi bi-plus-circle"></i>
            Добавить новую точку обслуживания
        </h5>
    </div>
    <div class="card-body">
        [Форма]
    </div>
</section>
```

**Таблица:**
```html
<table class="table table-hover">
    <thead class="table-light">
        [Заголовки]
    </thead>
    <tbody>
        [Строки с иконками]
    </tbody>
</table>
```

**Статусы:**
```html
<!-- Активна -->
<span class="badge bg-success">
    <i class="bi bi-check-circle"></i>
    Активна
</span>

<!-- Неактивна -->
<span class="badge bg-secondary">
    <i class="bi bi-x-circle"></i>
    Неактивна
</span>
```

**Кнопки:**
```html
<div class="btn-group btn-group-sm">
    <a class="btn btn-primary" title="Прайс-лист">
        <i class="bi bi-card-list"></i> Прайс
    </a>
    <a class="btn btn-secondary" title="Редактировать">
        <i class="bi bi-pencil"></i>
    </a>
    <a class="btn btn-warning" title="Деактивировать">
        <i class="bi bi-toggle-off"></i>
    </a>
</div>
```

---

## Валидация

### Серверная валидация

```php
// Название точки
if (empty($locationName)) {
    $error = 'Название точки обслуживания обязательно';
} elseif (strlen($locationName) > 100) {
    $error = 'Название не должно превышать 100 символов';
}

// Адрес
if (empty($address)) {
    $error = 'Адрес обязателен';
} elseif (strlen($address) > 255) {
    $error = 'Адрес не должен превышать 255 символов';
}

// Телефон
if (!empty($phone) && !preg_match('/^[\d\s\+\-\(\)]+$/', $phone)) {
    $error = 'Неверный формат телефона';
}
```

### HTML5 валидация

```html
<input type="text" 
       name="location_name"
       maxlength="100"
       required>

<input type="text" 
       name="address"
       maxlength="255"
       required>

<input type="tel"
       name="phone"
       maxlength="20">
```

---

## Тестирование

### Тест 1: Миграция данных

```sql
-- Проверка VIEW
SELECT * FROM address;

-- Проверка locations
SELECT * FROM locations;

-- Проверка маппинга в заказах
SELECT 
    oa.order_id,
    oa.address_id,
    oa.location_id,
    l.location_name,
    l.address
FROM order_address oa
LEFT JOIN locations l ON oa.location_id = l.location_id
LIMIT 10;
```

### Тест 2: Создание точки обслуживания

1. Откройте admin/locations.php
2. Заполните форму:
   - Название: "Копицентр Тестовый"
   - Адрес: "ул. Тестовая, 1"
   - Телефон: "+7 (999) 123-45-67"
   - Часы работы: "Пн-Пт: 9:00-18:00"
   - ✓ Активна
3. Нажмите "Добавить точку"
4. ✅ Должна появиться в списке

### Тест 3: Редактирование

1. Нажмите "Редактировать" на любой точке
2. Измените название
3. Нажмите "Сохранить изменения"
4. ✅ Изменения должны отобразиться

### Тест 4: Деактивация/Активация

1. Нажмите кнопку деактивации (toggle-off)
2. ✅ Статус → "Неактивна", badge серый
3. ✅ Кнопка меняется на активацию (toggle-on)
4. Нажмите активацию
5. ✅ Статус → "Активна", badge зеленый

### Тест 5: Оформление заказа

1. Откройте orders/create.php
2. Выберите услугу
3. В поле "Адрес организации" должны быть:
   ```
   [ ] Копицентр на Ленина - ул. Ленина, 1
   [ ] Копицентр Тестовый - ул. Тестовая, 1
   ```
4. ✅ Только активные точки в списке

### Тест 6: Переадресация

1. Откройте admin/addresses.php
2. ✅ Должна произойти переадресация на locations.php

---

## Откат изменений (если нужно)

### Восстановление старой структуры

```sql
-- 1. Переименовываем обратно
RENAME TABLE address_deprecated TO address;

-- 2. Удаляем VIEW
DROP VIEW IF EXISTS address;

-- 3. Восстанавливаем файл addresses.php из git
```

---

## Checklist внедрения

- [x] SQL миграция создана
- [x] Поля добавлены в locations
- [x] Данные перенесены из address
- [x] VIEW address создан
- [x] admin/locations.php обновлен
- [x] admin/addresses.php → переадресация
- [x] admin/index.php обновлен
- [x] orders/create.php обновлен
- [x] Валидация добавлена
- [x] UI/UX улучшен
- [x] Документация создана
- [ ] SQL миграция выполнена (выполните вручную!)
- [ ] Тестирование пройдено

---

## Файлы

### Созданы:
1. `database/merge_address_locations.sql` - SQL миграция
2. `database/MERGE_LOCATIONS.md` - эта документация

### Изменены:
1. `admin/locations.php` - полностью переписан
2. `admin/addresses.php` - переадресация
3. `admin/index.php` - обновлено меню
4. `orders/create.php` - запрос к locations

### Устарели:
1. Таблица `address` → переименована в `address_deprecated`

---

## Важно!

⚠️ **Выполните SQL-миграцию вручную:**

```bash
# В phpMyAdmin:
1. Выберите базу copypasteDB
2. Вкладка "SQL"
3. Откройте файл database/merge_address_locations.sql
4. Скопируйте и выполните
```

✅ **После миграции проверьте:**
- Точки обслуживания отображаются
- Заказы сохраняют адреса
- VIEW address работает

---

**Статус:** ✅ ГОТОВО  
**Требуется:** Выполнить SQL-миграцию
