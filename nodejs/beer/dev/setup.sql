-- create table
DROP TABLE beer;
CREATE TABLE beer (id INTEGER NOT NULL, name VARCHAR(100), quantity INTEGER, quantity_sold INTEGER, price DECIMAL(10,2), price_last DECIMAL(10,2), price_min DECIMAL(10,2), price_max DECIMAL(10,2), PRIMARY KEY (id), UNIQUE (name));

-- insert stock data
INSERT INTO beer VALUES (0, "Rolling Rock",		 24, 12, 1.00, 1.00, 1.00, 2.50);
INSERT INTO beer VALUES (1, "Pabst Blue Ribbon", 30,  8, 1.00, 1.00, 1.00, 2.50);
INSERT INTO beer VALUES (2, "Bud Light",		 24,  6, 1.50, 1.50, 1.50, 3.00);
INSERT INTO beer VALUES (3, "Busch Light",		 30, 10, 1.00, 1.00, 1.00, 2.50);
INSERT INTO beer VALUES (4, "Bell's Oberon",	  6,  3, 2.50, 2.50, 2.50, 4.00);
INSERT INTO beer VALUES (5, "Bell's Two-Hearted", 6,  1, 2.50, 2.50, 2.50, 4.00);
INSERT INTO beer VALUES (6, "Bell's Hopslam",	  6,  4, 4.00, 4.00, 4.00, 6.00);
INSERT INTO beer VALUES (7, "Bell's Black Note",  4,  3, 7.00, 7.00, 7.00, 9.00);
