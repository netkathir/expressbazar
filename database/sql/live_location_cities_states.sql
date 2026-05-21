START TRANSACTION;

SET @database_name = DATABASE();

SET @drop_old_city_unique = (
    SELECT IF(
        COUNT(*) > 0,
        'ALTER TABLE cities DROP INDEX cities_country_id_city_name_unique',
        'SELECT ''cities_country_id_city_name_unique not present'' AS message'
    )
    FROM information_schema.statistics
    WHERE table_schema = @database_name
      AND table_name = 'cities'
      AND index_name = 'cities_country_id_city_name_unique'
);
PREPARE drop_old_city_unique_stmt FROM @drop_old_city_unique;
EXECUTE drop_old_city_unique_stmt;
DEALLOCATE PREPARE drop_old_city_unique_stmt;

SET @add_country_index = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cities ADD INDEX idx_cities_country_id (country_id)',
        'SELECT ''idx_cities_country_id already present'' AS message'
    )
    FROM information_schema.statistics
    WHERE table_schema = @database_name
      AND table_name = 'cities'
      AND index_name = 'idx_cities_country_id'
);
PREPARE add_country_index_stmt FROM @add_country_index;
EXECUTE add_country_index_stmt;
DEALLOCATE PREPARE add_country_index_stmt;

SET @add_state_city_unique = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cities ADD UNIQUE cities_country_state_city_unique (country_id, state, city_name)',
        'SELECT ''cities_country_state_city_unique already present'' AS message'
    )
    FROM information_schema.statistics
    WHERE table_schema = @database_name
      AND table_name = 'cities'
      AND index_name = 'cities_country_state_city_unique'
);
PREPARE add_state_city_unique_stmt FROM @add_state_city_unique;
EXECUTE add_state_city_unique_stmt;
DEALLOCATE PREPARE add_state_city_unique_stmt;

INSERT INTO countries (country_name, country_code, currency, timezone, status, created_at, updated_at)
VALUES
    ('India', 'IN', 'INR', 'Asia/Kolkata', 'active', NOW(), NOW()),
    ('United Kingdom', 'UK', 'GBP', 'Europe/London', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    country_name = VALUES(country_name),
    currency = VALUES(currency),
    timezone = VALUES(timezone),
    status = 'active',
    updated_at = NOW();

CREATE TEMPORARY TABLE temp_location_cities (
    country_code VARCHAR(10) NOT NULL,
    state VARCHAR(255) NOT NULL,
    city_name VARCHAR(255) NOT NULL,
    city_code VARCHAR(50) NOT NULL,
    PRIMARY KEY (country_code, state, city_name)
);

INSERT INTO temp_location_cities (country_code, state, city_name, city_code) VALUES
('IN', 'Maharashtra', 'Mumbai', 'MUM'),
('IN', 'Delhi', 'Delhi', 'DEL'),
('IN', 'Karnataka', 'Bengaluru', 'BLR'),
('IN', 'Tamil Nadu', 'Chennai', 'CHE'),
('IN', 'Telangana', 'Hyderabad', 'HYD'),
('IN', 'West Bengal', 'Kolkata', 'KOL'),
('IN', 'Maharashtra', 'Pune', 'PUN'),
('IN', 'Gujarat', 'Ahmedabad', 'AMD'),
('IN', 'Tamil Nadu', 'Coimbatore', 'CBE'),
('IN', 'Tamil Nadu', 'Madurai', 'MDU'),
('IN', 'Tamil Nadu', 'Tiruchirappalli', 'TRZ'),
('IN', 'Tamil Nadu', 'Salem', 'SLM'),
('IN', 'Tamil Nadu', 'Erode', 'ERD'),
('IN', 'Tamil Nadu', 'Tirunelveli', 'TEN'),
('IN', 'Karnataka', 'Mysuru', 'MYS'),
('IN', 'Karnataka', 'Mangaluru', 'IXE'),
('IN', 'Karnataka', 'Hubballi', 'HBX'),
('IN', 'Kerala', 'Kochi', 'COK'),
('IN', 'Kerala', 'Thiruvananthapuram', 'TRV'),
('IN', 'Kerala', 'Kozhikode', 'CCJ'),
('IN', 'Andhra Pradesh', 'Visakhapatnam', 'VTZ'),
('IN', 'Andhra Pradesh', 'Vijayawada', 'VGA'),
('IN', 'Telangana', 'Warangal', 'WGL'),
('IN', 'Maharashtra', 'Nagpur', 'NAG'),
('IN', 'Maharashtra', 'Nashik', 'ISK'),
('IN', 'Maharashtra', 'Aurangabad', 'IXU'),
('IN', 'Gujarat', 'Surat', 'STV'),
('IN', 'Gujarat', 'Vadodara', 'BDQ'),
('IN', 'Gujarat', 'Rajkot', 'RAJ'),
('IN', 'Rajasthan', 'Jaipur', 'JAI'),
('IN', 'Uttar Pradesh', 'Lucknow', 'LKO'),
('IN', 'Uttar Pradesh', 'Kanpur', 'KNU'),
('IN', 'Chandigarh', 'Chandigarh', 'IXC'),
('IN', 'Odisha', 'Bhubaneswar', 'BBI'),
('IN', 'Bihar', 'Patna', 'PAT'),
('IN', 'Assam', 'Guwahati', 'GAU'),
('IN', 'Meghalaya', 'Shillong', 'SHL'),
('UK', 'England', 'Bath', 'BATH'),
('UK', 'England', 'Birmingham', 'BIR'),
('UK', 'England', 'Bradford', 'BRD'),
('UK', 'England', 'Brighton & Hove', 'BTN'),
('UK', 'England', 'Bristol', 'BRS'),
('UK', 'England', 'Cambridge', 'CBG'),
('UK', 'England', 'Canterbury', 'CTB'),
('UK', 'England', 'Carlisle', 'CAR'),
('UK', 'England', 'Chelmsford', 'CHM'),
('UK', 'England', 'Chester', 'CHS'),
('UK', 'England', 'Chichester', 'CCT'),
('UK', 'England', 'Colchester', 'COL'),
('UK', 'England', 'Coventry', 'COV'),
('UK', 'England', 'Derby', 'DER'),
('UK', 'England', 'Doncaster', 'DON'),
('UK', 'England', 'Durham', 'DUR'),
('UK', 'England', 'Ely', 'ELY'),
('UK', 'England', 'Exeter', 'EXT'),
('UK', 'England', 'Gloucester', 'GLO'),
('UK', 'England', 'Hereford', 'HER'),
('UK', 'England', 'Kingston-upon-Hull', 'HUL'),
('UK', 'England', 'Lancaster', 'LAN'),
('UK', 'England', 'Leeds', 'LEE'),
('UK', 'England', 'Leicester', 'LEI'),
('UK', 'England', 'Lichfield', 'LIC'),
('UK', 'England', 'Lincoln', 'LIN'),
('UK', 'England', 'Liverpool', 'LIV'),
('UK', 'England', 'London', 'LON'),
('UK', 'England', 'Manchester', 'MAN'),
('UK', 'England', 'Milton Keynes', 'MKY'),
('UK', 'England', 'Newcastle-upon-Tyne', 'NCL'),
('UK', 'England', 'Norwich', 'NOR'),
('UK', 'England', 'Nottingham', 'NOT'),
('UK', 'England', 'Oxford', 'OXF'),
('UK', 'England', 'Peterborough', 'PBO'),
('UK', 'England', 'Plymouth', 'PLY'),
('UK', 'England', 'Portsmouth', 'POR'),
('UK', 'England', 'Preston', 'PRE'),
('UK', 'England', 'Ripon', 'RIP'),
('UK', 'England', 'Salford', 'SAL'),
('UK', 'England', 'Salisbury', 'SLS'),
('UK', 'England', 'Sheffield', 'SHF'),
('UK', 'England', 'Southampton', 'SOU'),
('UK', 'England', 'Southend-on-Sea', 'SOS'),
('UK', 'England', 'St Albans', 'STA'),
('UK', 'England', 'Stoke on Trent', 'SOT'),
('UK', 'England', 'Sunderland', 'SUN'),
('UK', 'England', 'Truro', 'TRU'),
('UK', 'England', 'Wakefield', 'WAK'),
('UK', 'England', 'Wells', 'WEL'),
('UK', 'England', 'Westminster', 'WES'),
('UK', 'England', 'Winchester', 'WIN'),
('UK', 'England', 'Wolverhampton', 'WLV'),
('UK', 'England', 'Worcester', 'WOR'),
('UK', 'England', 'York', 'YRK'),
('UK', 'Northern Ireland', 'Armagh', 'ARM'),
('UK', 'Northern Ireland', 'Bangor', 'BNI'),
('UK', 'Northern Ireland', 'Belfast', 'BFS'),
('UK', 'Northern Ireland', 'Lisburn', 'LSB'),
('UK', 'Northern Ireland', 'Londonderry', 'LDY'),
('UK', 'Northern Ireland', 'Newry', 'NRY'),
('UK', 'Scotland', 'Aberdeen', 'ABD'),
('UK', 'Scotland', 'Dundee', 'DND'),
('UK', 'Scotland', 'Dunfermline', 'DFM'),
('UK', 'Scotland', 'Edinburgh', 'EDI'),
('UK', 'Scotland', 'Glasgow', 'GLA'),
('UK', 'Scotland', 'Inverness', 'INV'),
('UK', 'Scotland', 'Perth', 'PER'),
('UK', 'Scotland', 'Stirling', 'STI'),
('UK', 'Wales', 'Bangor', 'BGW'),
('UK', 'Wales', 'Cardiff', 'CDF'),
('UK', 'Wales', 'Newport', 'NWP'),
('UK', 'Wales', 'St Asaph', 'SAS'),
('UK', 'Wales', 'St Davids', 'STD'),
('UK', 'Wales', 'Swansea', 'SWA'),
('UK', 'Wales', 'Wrexham', 'WRX');

UPDATE cities c
JOIN countries co ON co.id = c.country_id
JOIN temp_location_cities t
    ON t.country_code = co.country_code
   AND t.city_name = c.city_name
SET
    c.state = t.state,
    c.city_code = t.city_code,
    c.status = 'active',
    c.updated_at = NOW()
WHERE co.country_code IN ('IN', 'UK')
  AND c.deleted_at IS NULL
  AND NOT (
      t.country_code = 'UK'
      AND t.city_name = 'Bangor'
      AND c.state <> t.state
  );

INSERT INTO cities (country_id, state, city_name, city_code, status, created_at, updated_at)
SELECT co.id, t.state, t.city_name, t.city_code, 'active', NOW(), NOW()
FROM temp_location_cities t
JOIN countries co ON co.country_code = t.country_code
LEFT JOIN cities c
    ON c.country_id = co.id
   AND c.state = t.state
   AND c.city_name = t.city_name
   AND c.deleted_at IS NULL
WHERE c.id IS NULL;

DROP TEMPORARY TABLE temp_location_cities;

SELECT
    co.country_name,
    co.country_code,
    COUNT(c.id) AS active_city_count,
    COUNT(DISTINCT c.state) AS active_state_count
FROM countries co
LEFT JOIN cities c
    ON c.country_id = co.id
   AND c.status = 'active'
   AND c.deleted_at IS NULL
WHERE co.country_code IN ('IN', 'UK')
GROUP BY co.id, co.country_name, co.country_code;

COMMIT;
