CREATE TABLE IF NOT EXISTS recipes (
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description VARCHAR(2048),
    tags  VARCHAR(255),
    image1 LONGBLOB,
    image2 LONGBLOB,
    image3 LONGBLOB
);

INSERT INTO recipes (title, description, tags) VALUES ('recipe-1', 'description-1', 'dessert');
INSERT INTO recipes (title, description, tags) VALUES ('recipe-2', 'description-2', 'entree');
INSERT INTO recipes (title, description, tags) VALUES ('recipe-3', 'description-3', 'plat');
INSERT INTO recipes (title, description, tags) VALUES ('recipe-4', 'description-4', 'plat');


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
