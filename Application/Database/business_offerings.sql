-- Business offering types and pricing rules for the catering order workflow.
-- Apply once after scope_alignment.sql and owner_account.sql.
-- This migration preserves existing menu, package, and reservation records.

ALTER TABLE menu_items
  ADD COLUMN unit_type ENUM('tray_25_35pax','per_pc','per_box','per_tray','per_layer','per_slice','per_person','each') NOT NULL DEFAULT 'tray_25_35pax' AFTER price,
  ADD COLUMN component_type ENUM('main_dish','side_dish','dessert','salad','rice','drink','alacarte') NOT NULL DEFAULT 'alacarte' AFTER unit_type;

ALTER TABLE catering_packages
  ADD COLUMN package_type ENUM('custom','packed_meal','buffet') NOT NULL DEFAULT 'custom' AFTER package_name,
  ADD COLUMN set_name VARCHAR(80) DEFAULT NULL AFTER package_type,
  ADD COLUMN business_key VARCHAR(100) DEFAULT NULL AFTER set_name,
  ADD UNIQUE KEY uq_catering_packages_business_key (business_key);

ALTER TABLE package_items
  ADD COLUMN component_type ENUM('main_dish','side_dish','dessert','salad','rice','drink','add_on','other') NOT NULL DEFAULT 'other' AFTER option_group,
  ADD COLUMN charge_type ENUM('per_event','per_person') NOT NULL DEFAULT 'per_event' AFTER additional_charge;

ALTER TABLE orders
  ADD COLUMN order_type ENUM('alacarte','packed_meal','buffet','custom') NOT NULL DEFAULT 'custom' AFTER package_id;

CREATE TABLE package_component_rules (
  package_id INT UNSIGNED NOT NULL,
  component_type ENUM('main_dish','side_dish','dessert','salad','rice','drink','add_on','other') NOT NULL,
  minimum_quantity SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  maximum_quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (package_id, component_type),
  CONSTRAINT fk_package_component_rules_package FOREIGN KEY (package_id) REFERENCES catering_packages (package_id) ON DELETE CASCADE,
  CONSTRAINT chk_package_component_rule_range CHECK (maximum_quantity >= minimum_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TEMPORARY TABLE tmp_business_products (
  name VARCHAR(255) NOT NULL,
  category VARCHAR(50) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  unit_type ENUM('tray_25_35pax','per_pc','per_box','per_tray','per_layer','per_slice','per_person','each') NOT NULL,
  component_type ENUM('main_dish','side_dish','dessert','salad','rice','drink','alacarte') NOT NULL
) ENGINE=Memory;

INSERT INTO tmp_business_products VALUES
('Beefsteak','Beef',1750,'tray_25_35pax','main_dish'),('Beef with Mushroom','Beef',1750,'tray_25_35pax','main_dish'),('Beefstew','Beef',1800,'tray_25_35pax','main_dish'),('Beef Caldereta','Beef',1850,'tray_25_35pax','main_dish'),('Beef Kare-Kare','Beef',1780,'tray_25_35pax','main_dish'),('Beef with Ampalaya','Beef',1680,'tray_25_35pax','main_dish'),('Roast Beef','Beef',1880,'tray_25_35pax','main_dish'),('Beef Pepper Steak','Beef',1750,'tray_25_35pax','main_dish'),('Lengua Estofado','Beef',1800,'tray_25_35pax','main_dish'),('Beef with Broccoli','Beef',1680,'tray_25_35pax','main_dish'),('Beef Embutido','Beef',1650,'tray_25_35pax','main_dish'),('Beef Machado','Beef',1600,'tray_25_35pax','main_dish'),
('Chicken Lollipop','Chicken',1400,'tray_25_35pax','main_dish'),('Chicken Apritada','Chicken',1300,'tray_25_35pax','main_dish'),('Buffalo Wings','Chicken',1400,'tray_25_35pax','main_dish'),('Buttered Chicken','Chicken',1450,'tray_25_35pax','main_dish'),('Chicken Curry','Chicken',1350,'tray_25_35pax','main_dish'),('Chicken Garlic','Chicken',1400,'tray_25_35pax','main_dish'),('Chicken Adobo','Chicken',1400,'tray_25_35pax','main_dish'),('Lemon Chicken','Chicken',1450,'tray_25_35pax','main_dish'),('Chicken and Mushroom','Chicken',1400,'tray_25_35pax','main_dish'),('Chicken Cordon Bleu','Chicken',1500,'tray_25_35pax','main_dish'),('Grilled Chicken with Gravy','Chicken',1550,'tray_25_35pax','main_dish'),('Chicken Teriyaki','Chicken',1500,'tray_25_35pax','main_dish'),('Chicken Salpicao','Chicken',1350,'tray_25_35pax','main_dish'),('Chicken Hawaiian','Chicken',1400,'tray_25_35pax','main_dish'),
('Hamonada','Pork',1700,'tray_25_35pax','main_dish'),('Lechon Kawali','Pork',1700,'tray_25_35pax','main_dish'),('Lumpia Shanghai','Pork',1500,'tray_25_35pax','main_dish'),('Meatloaf / Embutido','Pork',1650,'tray_25_35pax','main_dish'),('Siomai Tray','Pork',1250,'tray_25_35pax','main_dish'),('Porkchop','Pork',1600,'tray_25_35pax','main_dish'),('Sisig','Pork',1500,'tray_25_35pax','main_dish'),('Pork Stroganoff','Pork',1650,'tray_25_35pax','main_dish'),('Pork Kare-Kare','Pork',1700,'tray_25_35pax','main_dish'),('Pork Steak','Pork',1550,'tray_25_35pax','main_dish'),('Humba','Pork',1650,'tray_25_35pax','main_dish'),('Pork Apritada','Pork',1650,'tray_25_35pax','main_dish'),('Pork Caldereta','Pork',1650,'tray_25_35pax','main_dish'),('Pork with Ampalaya','Pork',1550,'tray_25_35pax','main_dish'),('Bola-Bola','Pork',1650,'tray_25_35pax','main_dish'),('Spareribs','Pork',1600,'tray_25_35pax','main_dish'),('Pork with Bagoong','Pork',1700,'tray_25_35pax','main_dish'),('Pork Asado','Pork',1650,'tray_25_35pax','main_dish'),('Sweet and Sour Pork','Pork',1550,'tray_25_35pax','main_dish'),('Grilled Liempo','Pork',1600,'tray_25_35pax','main_dish'),('Pork Teriyaki','Pork',1550,'tray_25_35pax','main_dish'),
('Four Season','Vegetables & Noodles',950,'tray_25_35pax','side_dish'),('Stir Fry Vegetables','Vegetables & Noodles',950,'tray_25_35pax','side_dish'),('Chopsuey Special','Vegetables & Noodles',1050,'tray_25_35pax','side_dish'),('Baked Mixed Vegetables','Vegetables & Noodles',1050,'tray_25_35pax','side_dish'),('Stir Fried Cabbage','Vegetables & Noodles',700,'tray_25_35pax','side_dish'),('Oven Baked Mixed Vegetables','Vegetables & Noodles',950,'tray_25_35pax','side_dish'),('Potato Carrot Balls','Vegetables & Noodles',900,'tray_25_35pax','side_dish'),('Bokchoy in Oyster Sauce','Vegetables & Noodles',850,'tray_25_35pax','side_dish'),('Garlic Butter Stir Fry','Vegetables & Noodles',850,'tray_25_35pax','side_dish'),('Ampalaya with Egg','Vegetables & Noodles',750,'tray_25_35pax','side_dish'),('Eggplant Omelet','Vegetables & Noodles',750,'tray_25_35pax','side_dish'),('Vegetable Curry','Vegetables & Noodles',800,'tray_25_35pax','side_dish'),('Roasted Broccoli','Vegetables & Noodles',800,'tray_25_35pax','side_dish'),('Bam-I','Vegetables & Noodles',900,'tray_25_35pax','side_dish'),('Pancit Canton Guisado','Vegetables & Noodles',900,'tray_25_35pax','side_dish'),('Fresh Pancit Guisado','Vegetables & Noodles',850,'tray_25_35pax','side_dish'),('Sotanghon Guisado','Vegetables & Noodles',900,'tray_25_35pax','side_dish'),('Chili Oil Noodles','Vegetables & Noodles',850,'tray_25_35pax','side_dish'),('Udon Noodles with Beef','Vegetables & Noodles',950,'tray_25_35pax','side_dish'),('Yakisoba Fried Noodles','Vegetables & Noodles',900,'tray_25_35pax','side_dish'),('Vegetable Chow Mein','Vegetables & Noodles',900,'tray_25_35pax','side_dish'),
('Fish Fillet with Tartar Dip','Seafoods',1650,'tray_25_35pax','main_dish'),('Fish Fillet with Tausi','Seafoods',1650,'tray_25_35pax','main_dish'),('Fruity Fish Teriyaki','Seafoods',1650,'tray_25_35pax','main_dish'),('Kinilaw','Seafoods',1850,'tray_25_35pax','main_dish'),('Shrimps in Peanut Sauce','Seafoods',1650,'tray_25_35pax','main_dish'),('Steamed Lapu-Lapu','Seafoods',1750,'tray_25_35pax','main_dish'),('Sweet and Sour Whole Fish','Seafoods',1750,'tray_25_35pax','main_dish'),('Fish Fillet with Lemon Sauce','Seafoods',1650,'tray_25_35pax','main_dish'),('Sinuglaw','Seafoods',1500,'tray_25_35pax','main_dish'),('Calamares','Seafoods',1500,'tray_25_35pax','main_dish'),('Rebosado','Seafoods',1600,'tray_25_35pax','main_dish'),('Fish Escabeche','Seafoods',1500,'tray_25_35pax','main_dish'),('Buttered Shrimp','Seafoods',1650,'tray_25_35pax','main_dish'),('Mixed Seafoods','Seafoods',1600,'tray_25_35pax','main_dish'),('Shrimp Roll','Seafoods',1600,'tray_25_35pax','main_dish'),
('Aglio Olio','Pasta',1150,'tray_25_35pax','side_dish'),('Baked Macaroni','Pasta',1300,'tray_25_35pax','side_dish'),('Baked Penne Pasta','Pasta',1250,'tray_25_35pax','side_dish'),('Baked Spaghetti','Pasta',1250,'tray_25_35pax','side_dish'),('Carbonara','Pasta',1250,'tray_25_35pax','side_dish'),('Creamy Spaghetti','Pasta',1200,'tray_25_35pax','side_dish'),('Lasagna (Beef / Pork)','Pasta',1500,'tray_25_35pax','side_dish'),
('Shrimp Pasta Salad','Salad',1350,'tray_25_35pax','salad'),('Fruit Salad','Salad',1000,'tray_25_35pax','salad'),('Macaroni Salad','Salad',1250,'tray_25_35pax','salad'),('Potato Salad','Salad',1350,'tray_25_35pax','salad'),('Crema de Fruita','Salad',1250,'tray_25_35pax','dessert'),('Mango Float','Salad',1200,'tray_25_35pax','dessert'),('Mango Tapioca','Salad',1150,'tray_25_35pax','dessert'),
('Puto Cheese','Snacks / Refreshments',10,'per_pc','dessert'),('Cheesy Bibingka','Snacks / Refreshments',15,'per_pc','dessert'),('Butterscotch','Snacks / Refreshments',900,'per_box','dessert'),('Suman with Latik','Snacks / Refreshments',600,'per_tray','dessert'),('Cuchinta','Snacks / Refreshments',10,'per_pc','dessert'),('Bake Siopao','Snacks / Refreshments',45,'each','alacarte'),('Siomai (piece)','Snacks / Refreshments',35,'each','alacarte'),('Creamy Spaghetti with Bread','Snacks / Refreshments',65,'each','alacarte'),('Carbonara with Bread','Snacks / Refreshments',80,'each','alacarte'),('Monte Cristo Sandwich','Snacks / Refreshments',100,'each','alacarte'),('Sticky Rice with Mango','Snacks / Refreshments',75,'each','dessert'),('Fresh Lumpia','Snacks / Refreshments',35,'each','alacarte'),('Torta','Snacks / Refreshments',25,'per_pc','dessert'),('Cassava Cake','Snacks / Refreshments',25,'per_pc','dessert'),('Carrot Cake','Snacks / Refreshments',600,'per_layer','dessert'),('Biko','Snacks / Refreshments',600,'per_tray','dessert'),('Maja Blanca','Snacks / Refreshments',20,'per_slice','dessert'),('Puto with Dinuguan','Snacks / Refreshments',65,'each','alacarte'),('Lasagna with Toasted Bread','Snacks / Refreshments',100,'each','alacarte'),('Tuna Sandwich','Snacks / Refreshments',90,'each','alacarte'),('Clubhouse Sandwich','Snacks / Refreshments',100,'each','alacarte'),('Cheesy Ensaymada','Snacks / Refreshments',60,'each','dessert'),('Cheeseburger','Snacks / Refreshments',35,'each','alacarte'),('Biko with Latik','Snacks / Refreshments',30,'each','dessert'),
('Steamed Rice','Rice',25,'per_person','rice'),('Garlic Rice','Rice',35,'per_person','rice'),('Java Rice','Rice',35,'per_person','rice'),
('C2 Solo','Add-ons',15,'per_person','drink'),('Coke Swakto','Add-ons',15,'per_person','drink'),('Tetra Juice','Add-ons',15,'per_person','drink'),('Water (300ml)','Add-ons',15,'per_person','drink'),('Coke Mismo','Add-ons',25,'per_person','drink'),('Drinks in Can (35)','Add-ons',35,'per_person','drink'),('Drinks in Can (45)','Add-ons',45,'per_person','drink'),('Softdrinks','Add-ons',0,'per_person','drink'),('Juice','Add-ons',0,'per_person','drink');

INSERT INTO tmp_business_products VALUES
('Battered Chicken','Chicken',1450,'tray_25_35pax','main_dish'),('Pancit Guisado','Vegetables & Noodles',850,'tray_25_35pax','side_dish'),('Garden Salad','Salad',1350,'tray_25_35pax','salad'),('Brownies','Snacks / Refreshments',700,'per_tray','dessert'),('Fresh Fruits','Snacks / Refreshments',1000,'per_tray','dessert'),('Beef Apritada','Beef',1650,'tray_25_35pax','main_dish'),('Revel Bars','Snacks / Refreshments',700,'per_tray','dessert'),('Sweet Chili Wings','Chicken',1450,'tray_25_35pax','main_dish');
UPDATE tmp_business_products SET price=1 WHERE name IN ('Softdrinks','Juice');

INSERT INTO menu_items (name, description, price, category, is_vegan, is_gluten_free, is_active, unit_type, component_type)
SELECT s.name, CASE s.unit_type
  WHEN 'tray_25_35pax' THEN 'One tray serves about 25–35 people.'
  WHEN 'per_person' THEN 'Priced per person.'
  WHEN 'per_pc' THEN 'Priced per piece.'
  WHEN 'per_box' THEN 'Priced per box.'
  WHEN 'per_tray' THEN 'Priced per tray.'
  WHEN 'per_layer' THEN 'Priced per layer.'
  WHEN 'per_slice' THEN 'Priced per slice.'
  ELSE 'Priced per item.'
END, s.price, s.category, 0, 0, 1, s.unit_type, s.component_type
FROM tmp_business_products s
WHERE NOT EXISTS (SELECT 1 FROM menu_items m WHERE LOWER(m.name)=LOWER(s.name) AND m.category=s.category);

UPDATE menu_items m JOIN tmp_business_products s ON LOWER(m.name)=LOWER(s.name) AND m.category=s.category
SET m.unit_type=s.unit_type, m.component_type=s.component_type;
UPDATE menu_items
SET description = CASE unit_type
  WHEN 'tray_25_35pax' THEN 'One tray serves about 25–35 people.'
  WHEN 'per_person' THEN 'Priced per person.'
  WHEN 'per_pc' THEN 'Priced per piece.'
  WHEN 'per_box' THEN 'Priced per box.'
  WHEN 'per_tray' THEN 'Priced per tray.'
  WHEN 'per_layer' THEN 'Priced per layer.'
  WHEN 'per_slice' THEN 'Priced per slice.'
  ELSE 'Priced per item.'
END
WHERE description LIKE 'Serves 25–35 people when ordered as a tray.%';
DROP TEMPORARY TABLE tmp_business_products;

INSERT INTO catering_packages (package_name, package_type, set_name, business_key, description, price_type, package_price, minimum_guests, maximum_guests, status)
SELECT s.package_name, s.package_type, s.set_name, s.business_key, s.description, 'Per Person', s.package_price, 50, NULL, 'Active'
FROM (
  SELECT 'Packed Meal Tier A' package_name,'packed_meal' package_type,'Tier A' set_name,'packed-tier-a' business_key,'1 main dish, 1 side dish, and 1 rice per person.' description,160.00 package_price UNION ALL
  SELECT 'Packed Meal Tier B','packed_meal','Tier B','packed-tier-b','1 main dish, 1 side dish, 1 dessert, and 1 rice per person.',180.00 UNION ALL
  SELECT 'Packed Meal Tier C','packed_meal','Tier C','packed-tier-c','1 main dish, 1 side dish, 1 dessert, 1 salad, and 1 rice per person.',200.00 UNION ALL
  SELECT 'Packed Meal Tier D','packed_meal','Tier D','packed-tier-d','2 main dishes, 1 side dish, 1 dessert, 1 salad, and 1 rice per person.',250.00 UNION ALL
  SELECT 'Buffet ₱350 · Set A','buffet','Set A','buffet-350-a','Buffet preset with service setup, utensils, water, tables and chairs for 50, and 2 waiters.',350.00 UNION ALL
  SELECT 'Buffet ₱350 · Set B','buffet','Set B','buffet-350-b','Buffet preset with service setup, utensils, water, tables and chairs for 50, and 2 waiters.',350.00 UNION ALL
  SELECT 'Buffet ₱350 · Set C','buffet','Set C','buffet-350-c','Buffet preset with service setup, utensils, water, tables and chairs for 50, and 2 waiters.',350.00 UNION ALL
  SELECT 'Buffet ₱400 · Set A','buffet','Set A','buffet-400-a','Upgraded buffet preset with service setup, utensils, water, tables and chairs for 50, and 2 waiters.',400.00 UNION ALL
  SELECT 'Buffet ₱400 · Set B','buffet','Set B','buffet-400-b','Upgraded buffet preset with service setup, utensils, water, tables and chairs for 50, and 2 waiters.',400.00 UNION ALL
  SELECT 'Buffet ₱400 · Set C','buffet','Set C','buffet-400-c','Upgraded buffet preset with service setup, utensils, water, tables and chairs for 50, and 2 waiters.',400.00
) s
WHERE NOT EXISTS (SELECT 1 FROM catering_packages p WHERE p.business_key=s.business_key);

-- Packed meal component requirements (Tier D requires two main dishes).
INSERT INTO package_component_rules (package_id, component_type, minimum_quantity, maximum_quantity)
SELECT p.package_id, r.component_type, r.minimum_quantity, r.maximum_quantity
FROM catering_packages p
JOIN (
  SELECT 'packed-tier-a' business_key,'main_dish' component_type,1 minimum_quantity,1 maximum_quantity UNION ALL SELECT 'packed-tier-a','side_dish',1,1 UNION ALL SELECT 'packed-tier-a','rice',1,1 UNION ALL SELECT 'packed-tier-a','dessert',0,0 UNION ALL SELECT 'packed-tier-a','salad',0,0 UNION ALL SELECT 'packed-tier-a','add_on',0,20 UNION ALL
  SELECT 'packed-tier-b','main_dish',1,1 UNION ALL SELECT 'packed-tier-b','side_dish',1,1 UNION ALL SELECT 'packed-tier-b','rice',1,1 UNION ALL SELECT 'packed-tier-b','dessert',1,1 UNION ALL SELECT 'packed-tier-b','salad',0,0 UNION ALL SELECT 'packed-tier-b','add_on',0,20 UNION ALL
  SELECT 'packed-tier-c','main_dish',1,1 UNION ALL SELECT 'packed-tier-c','side_dish',1,1 UNION ALL SELECT 'packed-tier-c','rice',1,1 UNION ALL SELECT 'packed-tier-c','dessert',1,1 UNION ALL SELECT 'packed-tier-c','salad',1,1 UNION ALL SELECT 'packed-tier-c','add_on',0,20 UNION ALL
  SELECT 'packed-tier-d','main_dish',2,2 UNION ALL SELECT 'packed-tier-d','side_dish',1,1 UNION ALL SELECT 'packed-tier-d','rice',1,1 UNION ALL SELECT 'packed-tier-d','dessert',1,1 UNION ALL SELECT 'packed-tier-d','salad',1,1 UNION ALL SELECT 'packed-tier-d','add_on',0,20
) r ON p.business_key=r.business_key
WHERE NOT EXISTS (SELECT 1 FROM package_component_rules x WHERE x.package_id=p.package_id AND x.component_type=r.component_type);

-- Choice pools for the packed meal tiers. Add-ons are charged per person.
INSERT INTO package_items (package_id, menu_item_id, option_group, component_type, selection_rule, additional_charge, charge_type)
SELECT p.package_id,m.id,r.component_type,r.component_type,
       IF(r.component_type='add_on' OR (p.business_key='packed-tier-d' AND r.component_type='main_dish'),'Optional Many','Required One'),
       IF(r.component_type='add_on',m.price,0),
       IF(r.component_type='add_on','per_person','per_event')
FROM catering_packages p
JOIN (
  SELECT 'packed-tier-a' business_key,'main_dish' component_type UNION ALL SELECT 'packed-tier-a','side_dish' UNION ALL SELECT 'packed-tier-a','rice' UNION ALL SELECT 'packed-tier-a','add_on' UNION ALL
  SELECT 'packed-tier-b','main_dish' UNION ALL SELECT 'packed-tier-b','side_dish' UNION ALL SELECT 'packed-tier-b','rice' UNION ALL SELECT 'packed-tier-b','dessert' UNION ALL SELECT 'packed-tier-b','add_on' UNION ALL
  SELECT 'packed-tier-c','main_dish' UNION ALL SELECT 'packed-tier-c','side_dish' UNION ALL SELECT 'packed-tier-c','rice' UNION ALL SELECT 'packed-tier-c','dessert' UNION ALL SELECT 'packed-tier-c','salad' UNION ALL SELECT 'packed-tier-c','add_on' UNION ALL
  SELECT 'packed-tier-d','main_dish' UNION ALL SELECT 'packed-tier-d','side_dish' UNION ALL SELECT 'packed-tier-d','rice' UNION ALL SELECT 'packed-tier-d','dessert' UNION ALL SELECT 'packed-tier-d','salad' UNION ALL SELECT 'packed-tier-d','add_on'
) r ON p.business_key=r.business_key
JOIN menu_items m ON ((r.component_type='add_on' AND m.category='Add-ons') OR m.component_type=r.component_type) AND m.is_active=1 AND (r.component_type <> 'rice' OR m.name='Steamed Rice')
WHERE NOT EXISTS (SELECT 1 FROM package_items x WHERE x.package_id=p.package_id AND x.menu_item_id=m.id AND x.option_group=r.component_type);

-- Exact preset contents for the buffet package sets.
CREATE TEMPORARY TABLE tmp_buffet_components (business_key VARCHAR(100), item_name VARCHAR(255), component_type VARCHAR(20)) ENGINE=Memory;
INSERT INTO tmp_buffet_components VALUES
('buffet-350-a','Pork Steak','main_dish'),('buffet-350-a','Sotanghon Guisado','side_dish'),('buffet-350-a','Four Season','side_dish'),('buffet-350-a','Baked Macaroni','side_dish'),('buffet-350-a','Fish Fillet with Tartar Dip','main_dish'),('buffet-350-a','Potato Salad','salad'),('buffet-350-a','Steamed Rice','rice'),('buffet-350-a','Softdrinks','drink'),
('buffet-350-b','Beefsteak','main_dish'),('buffet-350-b','Bam-I','side_dish'),('buffet-350-b','Chopsuey Special','side_dish'),('buffet-350-b','Sweet and Sour Whole Fish','main_dish'),('buffet-350-b','Battered Chicken','main_dish'),('buffet-350-b','Macaroni Salad','salad'),('buffet-350-b','Steamed Rice','rice'),('buffet-350-b','Softdrinks','drink'),
('buffet-350-c','Chicken Teriyaki','main_dish'),('buffet-350-c','Beef with Mushroom','main_dish'),('buffet-350-c','Sweet and Sour Whole Fish','main_dish'),('buffet-350-c','Pancit Guisado','side_dish'),('buffet-350-c','Buttered Shrimp','main_dish'),('buffet-350-c','Mango Tapioca','dessert'),('buffet-350-c','Steamed Rice','rice'),('buffet-350-c','Juice','drink'),
('buffet-400-a','Beef with Broccoli','main_dish'),('buffet-400-a','Battered Chicken','main_dish'),('buffet-400-a','Chopsuey Special','side_dish'),('buffet-400-a','Baked Macaroni','side_dish'),('buffet-400-a','Fish Fillet with Tausi','main_dish'),('buffet-400-a','Siomai Tray','side_dish'),('buffet-400-a','Garden Salad','salad'),('buffet-400-a','Steamed Rice','rice'),('buffet-400-a','Brownies','dessert'),('buffet-400-a','Softdrinks','drink'),
('buffet-400-b','Beefsteak','main_dish'),('buffet-400-b','Chicken Apritada','main_dish'),('buffet-400-b','Four Season','side_dish'),('buffet-400-b','Aglio Olio','side_dish'),('buffet-400-b','Sweet and Sour Whole Fish','main_dish'),('buffet-400-b','Bola-Bola','side_dish'),('buffet-400-b','Fruit Salad','salad'),('buffet-400-b','Steamed Rice','rice'),('buffet-400-b','Maja Blanca','dessert'),('buffet-400-b','Softdrinks','drink'),
('buffet-400-c','Sweet and Sour Whole Fish','main_dish'),('buffet-400-c','Chicken Salpicao','main_dish'),('buffet-400-c','Stir Fry Vegetables','side_dish'),('buffet-400-c','Humba','main_dish'),('buffet-400-c','Beef Apritada','main_dish'),('buffet-400-c','Buffalo Wings','main_dish'),('buffet-400-c','Fresh Fruits','dessert'),('buffet-400-c','Steamed Rice','rice'),('buffet-400-c','Revel Bars','dessert'),('buffet-400-c','Softdrinks','drink');

INSERT INTO package_items (package_id, menu_item_id, option_group, component_type, selection_rule, additional_charge, charge_type)
SELECT p.package_id,m.id,CONCAT('Included: ',s.item_name),s.component_type,'Required One',0,'per_person'
FROM tmp_buffet_components s
JOIN catering_packages p ON p.business_key=s.business_key
JOIN menu_items m ON LOWER(m.name)=LOWER(s.item_name)
WHERE NOT EXISTS (SELECT 1 FROM package_items x WHERE x.package_id=p.package_id AND x.menu_item_id=m.id AND x.option_group=CONCAT('Included: ',s.item_name));
DROP TEMPORARY TABLE tmp_buffet_components;

-- Keep each buffet's included dishes preselected, while allowing a customer to
-- swap an included item for another active dish of the same component type.
CREATE TEMPORARY TABLE tmp_buffet_option_groups ENGINE=Memory AS
SELECT DISTINCT preset.package_id, preset.option_group, preset.component_type
FROM package_items preset
JOIN catering_packages p ON p.package_id=preset.package_id AND p.package_type='buffet'
WHERE preset.option_group LIKE 'Included: %';

INSERT INTO package_items (package_id, menu_item_id, option_group, component_type, selection_rule, additional_charge, charge_type)
SELECT preset.package_id, alternative.id, preset.option_group, preset.component_type, 'Required One', 0, 'per_person'
FROM tmp_buffet_option_groups preset
JOIN menu_items alternative ON alternative.component_type=preset.component_type AND alternative.is_active=1
WHERE preset.option_group LIKE 'Included: %'
  AND NOT EXISTS (
    SELECT 1 FROM package_items existing
    WHERE existing.package_id=preset.package_id
      AND existing.menu_item_id=alternative.id
      AND existing.option_group=preset.option_group
  );
DROP TEMPORARY TABLE tmp_buffet_option_groups;

-- Package service inclusions are retained in the description; pricing is per guest.
