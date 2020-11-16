CREATE TABLE IF NOT EXISTS recipes (
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description VARCHAR(2047),
    category  VARCHAR(255),
    effort VARCHAR(255),
    image1 LONGBLOB,
    image2 LONGBLOB,
    image3 LONGBLOB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tags (
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    recipe_id INTEGER
);

INSERT INTO tags (name, recipe_id) VALUES ('vegetarian', 1);
INSERT INTO tags (name, recipe_id) VALUES ('vegetarian', 3);
INSERT INTO tags (name, recipe_id) VALUES ('vegetarian', 4);
INSERT INTO tags (name, recipe_id) VALUES ('vegan', 1);
INSERT INTO tags (name, recipe_id) VALUES ('fish', 5);

INSERT INTO recipes (title, description, category, effort, created_at, updated_at) VALUES ('recipe-1', 'description-1', '11-starter', '3-high', NOW(), NOW());
INSERT INTO recipes (title, description, category, effort, created_at, updated_at) VALUES ('recipe-2', 'description-2', '20-main', '2-medium', NOW(), NOW());
INSERT INTO recipes (title, description, category, effort, created_at, updated_at) VALUES ('recipe-3', 'description-3', '30-dessert', '2-medium', NOW(), NOW());
INSERT INTO recipes (title, description, category, effort, created_at, updated_at) VALUES ('recipe-4', 'description-4', '10-appetizer', '1-easy', NOW(), NOW());
INSERT INTO recipes (title, description, category, effort, created_at, updated_at) VALUES ('recipe-5', 'description-5', '20-main', '2-medium', NOW(), NOW());

SELECT DISTINCT effort FROM recipes ORDER BY effort ASC;
SELECT DISTINCT category FROM recipes ORDER BY category ASC;
SELECT DISTINCT name FROM tags ORDER BY name ASC;

SELECT id, title, description, category, effort, created_at, updated_at FROM recipes WHERE id=1;
SELECT id, title, description, category, effort, created_at, updated_at FROM recipes WHERE title LIKE '%reci%';
SELECT id, title, description, category, effort, created_at, updated_at FROM recipes WHERE description LIKE '%descr%';
SELECT id, title, description, category, effort, created_at, updated_at FROM recipes WHERE category LIKE '20-%';
SELECT id, title, description, category, effort, created_at, updated_at FROM recipes WHERE effort LIKE '2-%';
SELECT id, title, description, category, effort, created_at, updated_at FROM recipes WHERE title LIKE '%' AND description LIKE '%' AND category LIKE '%' AND effort LIKE '%';

SELECT r.id, r.title, r.description, r.category, r.effort, IFNULL(GROUP_CONCAT(t.name), '') AS tags, r.created_at, r.updated_at
FROM recipes r
         LEFT JOIN tags t ON r.id = t.recipe_id
WHERE t.name LIKE '%' AND title LIKE '%' AND description LIKE '%' AND category LIKE '%' AND effort LIKE '%'
GROUP BY r.id;

BEGIN;
INSERT INTO recipes (title, description, category, effort, created_at, updated_at)
VALUES('title-112', 'description-112', '11-starter', '2-medium', NOW(), NOW());
INSERT INTO tags (name, recipe_id) VALUES ('vegetarian', LAST_INSERT_ID());
COMMIT;


-- category values:
-- 10-appetizer
-- 11-starter
-- 20-main
-- 30-dessert


-- effort-values:
-- 1-easy
-- 2-medium
-- 3-high

-- tags
-- vegetarian
-- vegan
-- fish

CREATE TABLE IF NOT EXISTS users (
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    forename VARCHAR(255),
    surname  VARCHAR(255)
);

INSERT INTO users (email, forename, surname) VALUES ('christian.egli4@gmail.com', 'Christian', 'Egli');
INSERT INTO users (email, forename, surname) VALUES ('joelle.egli@gmail.com', 'Joelle', 'Egli');
INSERT INTO users (email, forename, surname) VALUES ('zoe.egli@gmail.com', 'Zoé', 'Egli');
INSERT INTO users (email, forename, surname) VALUES ('liv.egli7@gmail.com', 'Liv', 'Egli');
