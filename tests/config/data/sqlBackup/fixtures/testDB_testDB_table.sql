USE testDB;
SET NAMES utf8mb4;

INSERT INTO `testDB_table` (`id`, `titre`, `date_creation`, `fk_id_table2`, `fk_id_table3`) VALUES(1, 'Accessoires', '2010-12-31 00:00:00', 1, 3),(2, 'Parchemins', '2010-12-31 00:00:00', 3, 2),(3, 'Potions', '2010-12-31 00:00:00', 2, 3);
