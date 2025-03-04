BEGIN;

-- Create user roles table
CREATE TABLE IF NOT EXISTS user_roles(
    role_id INT NOT NULL PRIMARY KEY,
    role_name VARCHAR(255) NOT NULL
);

-- Create users for CiLogon table
CREATE TABLE IF NOT EXISTS users(
    id VARCHAR(255) NOT NULL PRIMARY KEY,
    email VARCHAR(255),
    phone_number VARCHAR(255),
    timezone VARCHAR(255),
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    full_name VARCHAR(255),
    eppn VARCHAR(255),
    idp VARCHAR(255),
    idp_name VARCHAR(255),
    affiliation VARCHAR(255),
    roles INT[] DEFAULT ARRAY[0]
);


-- Create user_sessions table
CREATE TABLE IF NOT EXISTS user_sessions(
    session_id VARCHAR(36) NOT NULL PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    expires TIMESTAMP NOT NULL,
    token JSONB NOT NULL,
    CONSTRAINT user_sessions_users_id_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Create the protocol_types table
CREATE TABLE IF NOT EXISTS protocol_types (
    protocol_type_uuid UUID NOT NULL PRIMARY KEY,
    study VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- Create the participants table
CREATE TABLE IF NOT EXISTS participants (
    participant_uuid UUID NOT NULL PRIMARY KEY,
    study VARCHAR(255) NOT NULL,
    participant_json JSONB NOT NULL
);

-- Create the enrollments table
CREATE TABLE IF NOT EXISTS enrollments (
    enrollment_uuid UUID NOT NULL PRIMARY KEY,
    participant_uuid UUID NOT NULL REFERENCES participants(participant_uuid) ON UPDATE CASCADE ON DELETE CASCADE,
    protocol_type_uuid UUID NOT NULL REFERENCES protocol_types(protocol_type_uuid) ON UPDATE CASCADE ON DELETE CASCADE,
    status BOOLEAN NOT NULL
);

-- Create the messages table
CREATE TABLE IF NOT EXISTS messages (
    message_uuid UUID NOT NULL PRIMARY KEY,
    participant_uuid UUID NOT NULL REFERENCES participants(participant_uuid) ON UPDATE CASCADE ON DELETE CASCADE,
    TS TIMESTAMP NOT NULL,
    message_direction CHAR(8) NOT NULL, /* incoming or outgoing */
    message_json JSONB NOT NULL,
    study VARCHAR(255) NOT NULL
);

-- Create the queued_messages table
CREATE TABLE IF NOT EXISTS queued_messages (
    message_uuid UUID NOT NULL PRIMARY KEY,
    participant_uuid UUID NOT NULL REFERENCES participants(participant_uuid) ON UPDATE CASCADE ON DELETE CASCADE,
    toNumber CHAR(12) NOT NULL,
    fromNumber CHAR(12) NOT NULL,
    scheduledFor TIMESTAMP NOT NULL,
    message_json JSONB NOT NULL,
    study VARCHAR(255) NOT NULL
);


-- Create the save_state table
CREATE TABLE IF NOT EXISTS save_state (
    enrollment_uuid UUID NOT NULL REFERENCES enrollments(enrollment_uuid) ON UPDATE CASCADE ON DELETE CASCADE,
    TS TIMESTAMP NOT NULL,
    state_json JSONB NOT NULL
);

-- Create the state_log table
CREATE TABLE IF NOT EXISTS state_log (
    participant_uuid UUID NOT NULL,
    TS TIMESTAMP NOT NULL,
    log_json JSONB NOT NULL
);

-- Create the surveys table
CREATE TABLE IF NOT EXISTS surveys (
    token UUID NOT NULL,
    participant_uuid UUID NOT NULL,
    created_at TIMESTAMP NOT NULL,
    finished_at TIMESTAMP,
    survey_json JSONB
);

-- Create the time_zones table
CREATE TABLE IF NOT EXISTS time_zones (
    time_zone VARCHAR(64) NOT NULL,
    short_zone VARCHAR(32) NOT NULL,
    UNIQUE (time_zone, short_zone)
);

-- Create the metrics table
CREATE TABLE IF NOT EXISTS metrics (
    metric_id UUID PRIMARY KEY,
    participant_id UUID,
    ts TIMESTAMP NOT NULL DEFAULT (NOW() AT TIME ZONE 'utc'),
    metric_json jsonb NOT NULL,
    FOREIGN KEY (participant_id) REFERENCES participants(participant_uuid)
);

COMMIT;


BEGIN;

INSERT INTO user_roles (role_id, role_name) VALUES
    (0, 'Default'),
    (1, 'Super Admin'), -- developers or maintainers of the site
    (2, 'Study Admin'), -- PI or study coordinator
    (3, 'User') -- Postdoc, intern, undergrad, etc.
ON CONFLICT (role_id) DO NOTHING;
-- Add more roles here if necessary

-- Constraint for ensuring roles are valid
CREATE OR REPLACE FUNCTION check_roles_validity() 
RETURNS TRIGGER AS $$
BEGIN
    -- Check that every value in the roles array exists in user_roles
    IF EXISTS (
        SELECT 1 FROM unnest(NEW.roles) AS role_id 
        WHERE role_id NOT IN (SELECT role_id FROM user_roles)
    ) THEN
        RAISE EXCEPTION 'Invalid role_id in roles array';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER validate_roles
BEFORE INSERT OR UPDATE ON users
FOR EACH ROW EXECUTE FUNCTION check_roles_validity();
COMMIT;

BEGIN;
-- Insert initial user into the database as an Admin. <ADMIN_EPPN> will be updated to the .env value 
-- when the container is built
INSERT INTO users (id, eppn, roles) VALUES
    ('notloggedin_123456', '<ADMIN_EPPN>', ARRAY[(SELECT role_id FROM user_roles WHERE role_name = 'Super Admin')]);

-- Insert timezones into time_zone table
INSERT INTO time_zones (time_zone, short_zone)
VALUES
    ('Africa/Abidjan', 'Africa'),
    ('Africa/Abidjan', 'Africa'),
    ('Africa/Accra', 'Africa'),
    ('Africa/Addis_Ababa', 'Africa'),
    ('Africa/Algiers', 'Africa'),
    ('Africa/Asmara', 'Africa'),
    ('Africa/Asmera', 'Africa'),
    ('Africa/Bamako', 'Africa'),
    ('Africa/Bangui', 'Africa'),
    ('Africa/Banjul', 'Africa'),
    ('Africa/Bissau', 'Africa'),
    ('Africa/Blantyre', 'Africa'),
    ('Africa/Brazzaville', 'Africa'),
    ('Africa/Bujumbura', 'Africa'),
    ('Africa/Cairo', 'Africa'),
    ('Africa/Casablanca', 'Africa'),
    ('Africa/Ceuta', 'Africa'),
    ('Africa/Conakry', 'Africa'),
    ('Africa/Dakar', 'Africa'),
    ('Africa/Dar_es_Salaam', 'Africa'),
    ('Africa/Djibouti', 'Africa'),
    ('Africa/Douala', 'Africa'),
    ('Africa/El_Aaiun', 'Africa'),
    ('Africa/Freetown', 'Africa'),
    ('Africa/Gaborone', 'Africa'),
    ('Africa/Harare', 'Africa'),
    ('Africa/Johannesburg', 'Africa'),
    ('Africa/Juba', 'Africa'),
    ('Africa/Kampala', 'Africa'),
    ('Africa/Khartoum', 'Africa'),
    ('Africa/Kigali', 'Africa'),
    ('Africa/Kinshasa', 'Africa'),
    ('Africa/Lagos', 'Africa'),
    ('Africa/Libreville', 'Africa'),
    ('Africa/Lome', 'Africa'),
    ('Africa/Luanda', 'Africa'),
    ('Africa/Lubumbashi', 'Africa'),
    ('Africa/Lusaka', 'Africa'),
    ('Africa/Malabo', 'Africa'),
    ('Africa/Maputo', 'Africa'),
    ('Africa/Maseru', 'Africa'),
    ('Africa/Mbabane', 'Africa'),
    ('Africa/Mogadishu', 'Africa'),
    ('Africa/Monrovia', 'Africa'),
    ('Africa/Nairobi', 'Africa'),
    ('Africa/Ndjamena', 'Africa'),
    ('Africa/Niamey', 'Africa'),
    ('Africa/Nouakchott', 'Africa'),
    ('Africa/Ouagadougou', 'Africa'),
    ('Africa/Porto-Novo', 'Africa'),
    ('Africa/Sao_Tome', 'Africa'),
    ('Africa/Timbuktu', 'Africa'),
    ('Africa/Tripoli', 'Africa'),
    ('Africa/Tunis', 'Africa'),
    ('Africa/Windhoek', 'Africa'),
    ('America/Adak', 'America'),
    ('America/Anchorage', 'America'),
    ('America/Anguilla', 'America'),
    ('America/Antigua', 'America'),
    ('America/Araguaina', 'America'),
    ('America/Argentina/Buenos_Aires', 'America'),
    ('America/Argentina/Catamarca', 'America'),
    ('America/Argentina/ComodRivadavia', 'America'),
    ('America/Argentina/Cordoba', 'America'),
    ('America/Argentina/Jujuy', 'America'),
    ('America/Argentina/La_Rioja', 'America'),
    ('America/Argentina/Mendoza', 'America'),
    ('America/Argentina/Rio_Gallegos', 'America'),
    ('America/Argentina/Salta', 'America'),
    ('America/Argentina/San_Juan', 'America'),
    ('America/Argentina/San_Luis', 'America'),
    ('America/Argentina/Tucuman', 'America'),
    ('America/Argentina/Ushuaia', 'America'),
    ('America/Aruba', 'America'),
    ('America/Asuncion', 'America'),
    ('America/Atikokan', 'America'),
    ('America/Atka', 'America'),
    ('America/Bahia', 'America'),
    ('America/Bahia_Banderas', 'America'),
    ('America/Barbados', 'America'),
    ('America/Belem', 'America'),
    ('America/Belize', 'America'),
    ('America/Blanc-Sablon', 'America'),
    ('America/Boa_Vista', 'America'),
    ('America/Bogota', 'America'),
    ('America/Boise', 'America'),
    ('America/Buenos_Aires', 'America'),
    ('America/Cambridge_Bay', 'America'),
    ('America/Campo_Grande', 'America'),
    ('America/Cancun', 'America'),
    ('America/Caracas', 'America'),
    ('America/Catamarca', 'America'),
    ('America/Cayenne', 'America'),
    ('America/Cayman', 'America'),
    ('America/Chicago', 'America'),
    ('America/Chihuahua', 'America'),
    ('America/Coral_Harbour', 'America'),
    ('America/Cordoba', 'America'),
    ('America/Costa_Rica', 'America'),
    ('America/Creston', 'America'),
    ('America/Cuiaba', 'America'),
    ('America/Curacao', 'America'),
    ('America/Danmarkshavn', 'America'),
    ('America/Dawson', 'America'),
    ('America/Dawson_Creek', 'America'),
    ('America/Denver', 'America'),
    ('America/Detroit', 'America'),
    ('America/Dominica', 'America'),
    ('America/Edmonton', 'America'),
    ('America/Eirunepe', 'America'),
    ('America/El_Salvador', 'America'),
    ('America/Ensenada', 'America'),
    ('America/Fort_Nelson', 'America'),
    ('America/Fort_Wayne', 'America'),
    ('America/Fortaleza', 'America'),
    ('America/Glace_Bay', 'America'),
    ('America/Godthab', 'America'),
    ('America/Goose_Bay', 'America'),
    ('America/Grand_Turk', 'America'),
    ('America/Grenada', 'America'),
    ('America/Guadeloupe', 'America'),
    ('America/Guatemala', 'America'),
    ('America/Guayaquil', 'America'),
    ('America/Guyana', 'America'),
    ('America/Halifax', 'America'),
    ('America/Havana', 'America'),
    ('America/Hermosillo', 'America'),
    ('America/Indiana/Indianapolis', 'America'),
    ('America/Indiana/Knox', 'America'),
    ('America/Indiana/Marengo', 'America'),
    ('America/Indiana/Petersburg', 'America'),
    ('America/Indiana/Tell_City', 'America'),
    ('America/Indiana/Vevay', 'America'),
    ('America/Indiana/Vincennes', 'America'),
    ('America/Indiana/Winamac', 'America'),
    ('America/Indianapolis', 'America'),
    ('America/Inuvik', 'America'),
    ('America/Iqaluit', 'America'),
    ('America/Jamaica', 'America'),
    ('America/Jujuy', 'America'),
    ('America/Juneau', 'America'),
    ('America/Kentucky/Louisville', 'America'),
    ('America/Kentucky/Monticello', 'America'),
    ('America/Knox_I', 'America'),
    ('America/Kralendijk', 'America'),
    ('America/La_Paz', 'America'),
    ('America/Lima', 'America'),
    ('America/Los_Angeles', 'America'),
    ('America/Louisville', 'America'),
    ('America/Lower_Princes', 'America'),
    ('America/Maceio', 'America'),
    ('America/Managua', 'America'),
    ('America/Manaus', 'America'),
    ('America/Marigot', 'America'),
    ('America/Martinique', 'America'),
    ('America/Matamoros', 'America'),
    ('America/Mazatlan', 'America'),
    ('America/Mendoza', 'America'),
    ('America/Menominee', 'America'),
    ('America/Merida', 'America'),
    ('America/Metlakatla', 'America'),
    ('America/Mexico_City', 'America'),
    ('America/Miquelon', 'America'),
    ('America/Moncton', 'America'),
    ('America/Monterrey', 'America'),
    ('America/Montevideo', 'America'),
    ('America/Montreal', 'America'),
    ('America/Montserrat', 'America'),
    ('America/Nassau', 'America'),
    ('America/New_York', 'America'),
    ('America/Nipigon', 'America'),
    ('America/Nome', 'America'),
    ('America/Noronha', 'America'),
    ('America/North_Dakota/Beulah', 'America'),
    ('America/North_Dakota/Center', 'America'),
    ('America/North_Dakota/New_Salem', 'America'),
    ('America/Ojinaga', 'America'),
    ('America/Panama', 'America'),
    ('America/Pangnirtung', 'America'),
    ('America/Paramaribo', 'America'),
    ('America/Phoenix', 'America'),
    ('America/Port-au-Prince', 'America'),
    ('America/Port_of_Spain', 'America'),
    ('America/Porto_Acre', 'America'),
    ('America/Porto_Velho', 'America'),
    ('America/Puerto_Rico', 'America'),
    ('America/Punta_Arenas', 'America'),
    ('America/Rainy_River', 'America'),
    ('America/Rankin_Inlet', 'America'),
    ('America/Recife', 'America'),
    ('America/Regina', 'America'),
    ('America/Resolute', 'America'),
    ('America/Rio_Branco', 'America'),
    ('America/Rosario', 'America'),
    ('America/Santa_Isabel', 'America'),
    ('America/Santarem', 'America'),
    ('America/Santiago', 'America'),
    ('America/Santo_Domingo', 'America'),
    ('America/Sao_Paulo', 'America'),
    ('America/Scoresbysund', 'America'),
    ('America/Shiprock', 'America'),
    ('America/Sitka', 'America'),
    ('America/St_Barthelemy', 'America'),
    ('America/St_Johns', 'America'),
    ('America/St_Kitts', 'America'),
    ('America/St_Lucia', 'America'),
    ('America/St_Thomas', 'America'),
    ('America/St_Vincent', 'America'),
    ('America/Swift_Current', 'America'),
    ('America/Tegucigalpa', 'America'),
    ('America/Thule', 'America'),
    ('America/Thunder_Bay', 'America'),
    ('America/Tijuana', 'America'),
    ('America/Toronto', 'America'),
    ('America/Tortola', 'America'),
    ('America/Vancouver', 'America'),
    ('America/Virgin', 'America'),
    ('America/Whitehorse', 'America'),
    ('America/Winnipeg', 'America'),
    ('America/Yakutat', 'America'),
    ('America/Yellowknife', 'America'),
    ('Antarctica/Casey', 'Antarctica'),
    ('Antarctica/Davis', 'Antarctica'),
    ('Antarctica/DumontDUrville', 'Antarctica'),
    ('Antarctica/Macquarie', 'Antarctica'),
    ('Antarctica/Mawson', 'Antarctica'),
    ('Antarctica/McMurdo', 'Antarctica'),
    ('Antarctica/Palmer', 'Antarctica'),
    ('Antarctica/Rothera', 'Antarctica'),
    ('Antarctica/South_Pole', 'Antarctica'),
    ('Antarctica/Syowa', 'Antarctica'),
    ('Antarctica/Troll', 'Antarctica'),
    ('Antarctica/Vostok', 'Antarctica'),
    ('Arctic/Longyearbyen', 'Arctic'),
    ('Asia/Aden', 'Asia'),
    ('Asia/Almaty', 'Asia'),
    ('Asia/Amman', 'Asia'),
    ('Asia/Anadyr', 'Asia'),
    ('Asia/Aqtau', 'Asia'),
    ('Asia/Aqtobe', 'Asia'),
    ('Asia/Ashgabat', 'Asia'),
    ('Asia/Ashkhabad', 'Asia'),
    ('Asia/Atyrau', 'Asia'),
    ('Asia/Baghdad', 'Asia'),
    ('Asia/Bahrain', 'Asia'),
    ('Asia/Baku', 'Asia'),
    ('Asia/Bangkok', 'Asia'),
    ('Asia/Barnaul', 'Asia'),
    ('Asia/Beirut', 'Asia'),
    ('Asia/Bishkek', 'Asia'),
    ('Asia/Brunei', 'Asia'),
    ('Asia/Calcutta', 'Asia'),
    ('Asia/Chita', 'Asia'),
    ('Asia/Choibalsan', 'Asia'),
    ('Asia/Chongqing', 'Asia'),
    ('Asia/Chungking', 'Asia'),
    ('Asia/Colombo', 'Asia'),
    ('Asia/Dacca', 'Asia'),
    ('Asia/Damascus', 'Asia'),
    ('Asia/Dhaka', 'Asia'),
    ('Asia/Dili', 'Asia'),
    ('Asia/Dubai', 'Asia'),
    ('Asia/Dushanbe', 'Asia'),
    ('Asia/Famagusta', 'Asia'),
    ('Asia/Gaza', 'Asia'),
    ('Asia/Harbin', 'Asia'),
    ('Asia/Hebron', 'Asia'),
    ('Asia/Ho_Chi_Minh', 'Asia'),
    ('Asia/Hong_Kong', 'Asia'),
    ('Asia/Hovd', 'Asia'),
    ('Asia/Irkutsk', 'Asia'),
    ('Asia/Istanbul', 'Asia'),
    ('Asia/Jakarta', 'Asia'),
    ('Asia/Jayapura', 'Asia'),
    ('Asia/Jerusalem', 'Asia'),
    ('Asia/Kabul', 'Asia'),
    ('Asia/Kamchatka', 'Asia'),
    ('Asia/Karachi', 'Asia'),
    ('Asia/Kashgar', 'Asia'),
    ('Asia/Kathmandu', 'Asia'),
    ('Asia/Katmandu', 'Asia'),
    ('Asia/Khandyga', 'Asia'),
    ('Asia/Kolkata', 'Asia'),
    ('Asia/Krasnoyarsk', 'Asia'),
    ('Asia/Kuala_Lumpur', 'Asia'),
    ('Asia/Kuching', 'Asia'),
    ('Asia/Kuwait', 'Asia'),
    ('Asia/Macao', 'Asia'),
    ('Asia/Macau', 'Asia'),
    ('Asia/Magadan', 'Asia'),
    ('Asia/Makassar', 'Asia'),
    ('Asia/Manila', 'Asia'),
    ('Asia/Muscat', 'Asia'),
    ('Asia/Nicosia', 'Asia'),
    ('Asia/Novokuznetsk', 'Asia'),
    ('Asia/Novosibirsk', 'Asia'),
    ('Asia/Omsk', 'Asia'),
    ('Asia/Oral', 'Asia'),
    ('Asia/Phnom_Penh', 'Asia'),
    ('Asia/Pontianak', 'Asia'),
    ('Asia/Pyongyang', 'Asia'),
    ('Asia/Qatar', 'Asia'),
    ('Asia/Qostanay', 'Asia'),
    ('Asia/Qyzylorda', 'Asia'),
    ('Asia/Rangoon', 'Asia'),
    ('Asia/Riyadh', 'Asia'),
    ('Asia/Saigon', 'Asia'),
    ('Asia/Sakhalin', 'Asia'),
    ('Asia/Samarkand', 'Asia'),
    ('Asia/Seoul', 'Asia'),
    ('Asia/Shanghai', 'Asia'),
    ('Asia/Singapore', 'Asia'),
    ('Asia/Srednekolymsk', 'Asia'),
    ('Asia/Taipei', 'Asia'),
    ('Asia/Tashkent', 'Asia'),
    ('Asia/Tbilisi', 'Asia'),
    ('Asia/Tehran', 'Asia'),
    ('Asia/Tel_Aviv', 'Asia'),
    ('Asia/Thimbu', 'Asia'),
    ('Asia/Thimphu', 'Asia'),
    ('Asia/Tokyo', 'Asia'),
    ('Asia/Tomsk', 'Asia'),
    ('Asia/Ujung_Pandang', 'Asia'),
    ('Asia/Ulaanbaatar', 'Asia'),
    ('Asia/Ulan_Bator', 'Asia'),
    ('Asia/Urumqi', 'Asia'),
    ('Asia/Ust-Nera', 'Asia'),
    ('Asia/Vientiane', 'Asia'),
    ('Asia/Vladivostok', 'Asia'),
    ('Asia/Yakutsk', 'Asia'),
    ('Asia/Yangon', 'Asia'),
    ('Asia/Yekaterinburg', 'Asia'),
    ('Asia/Yerevan', 'Asia'),
    ('Atlantic/Azores', 'Atlantic'),
    ('Atlantic/Bermuda', 'Atlantic'),
    ('Atlantic/Canary', 'Atlantic'),
    ('Atlantic/Cape_Verde', 'Atlantic'),
    ('Atlantic/Faeroe', 'Atlantic'),
    ('Atlantic/Faroe', 'Atlantic'),
    ('Atlantic/Jan_Mayen', 'Atlantic'),
    ('Atlantic/Madeira', 'Atlantic'),
    ('Atlantic/Reykjavik', 'Atlantic'),
    ('Atlantic/South_Georgia', 'Atlantic'),
    ('Atlantic/St_Helena', 'Atlantic'),
    ('Atlantic/Stanley', 'Atlantic'),
    ('Australia/ACT', 'Australia'),
    ('Australia/Adelaide', 'Australia'),
    ('Australia/Brisbane', 'Australia'),
    ('Australia/Broken_Hill', 'Australia'),
    ('Australia/Canberra', 'Australia'),
    ('Australia/Currie', 'Australia'),
    ('Australia/Darwin', 'Australia'),
    ('Australia/Eucla', 'Australia'),
    ('Australia/Hobart', 'Australia'),
    ('Australia/LHI', 'Australia'),
    ('Australia/Lindeman', 'Australia'),
    ('Australia/Lord_Howe', 'Australia'),
    ('Australia/Melbourne', 'Australia'),
    ('Australia/North', 'Australia'),
    ('Australia/NSW', 'Australia'),
    ('Australia/Perth', 'Australia'),
    ('Australia/Queensland', 'Australia'),
    ('Australia/South', 'Australia'),
    ('Australia/Sydney', 'Australia'),
    ('Australia/Tasmania', 'Australia'),
    ('Australia/Victoria', 'Australia'),
    ('Australia/West', 'Australia'),
    ('Australia/Yancowinna', 'Australia'),
    ('Brazil/Acre', 'Brazil'),
    ('Brazil/DeNoronha', 'Brazil'),
    ('Brazil/East', 'Brazil'),
    ('Brazil/West', 'Brazil'),
    ('Canada/Atlantic', 'Canada'),
    ('Canada/Central', 'Canada'),
    ('Canada/Eastern', 'Canada'),
    ('Canada/Mountain', 'Canada'),
    ('Canada/Newfoundland', 'Canada'),
    ('Canada/Pacific', 'Canada'),
    ('Canada/Saskatchewan', 'Canada'),
    ('Canada/Yukon', 'Canada'),
    ('CET', 'CET'),
    ('Chile/Continental', 'Chile'),
    ('Chile/EasterIsland', 'Chile'),
    ('CST6CDT', 'CST6CDT'),
    ('Cuba', 'Cuba'),
    ('EET', 'EET'),
    ('Egypt', 'Egypt'),
    ('Eire', 'Eire'),
    ('EST5EDT', 'EST5EDT'),
    ('Etc/GMT', 'Etc'),
    ('Etc/GMT+0', 'Etc'),
    ('Etc/GMT+1', 'Etc'),
    ('Etc/GMT+10', 'Etc'),
    ('Etc/GMT+11', 'Etc'),
    ('Etc/GMT+12', 'Etc'),
    ('Etc/GMT+2', 'Etc'),
    ('Etc/GMT+3', 'Etc'),
    ('Etc/GMT+4', 'Etc'),
    ('Etc/GMT+5', 'Etc'),
    ('Etc/GMT+6', 'Etc'),
    ('Etc/GMT+7', 'Etc'),
    ('Etc/GMT+8', 'Etc'),
    ('Etc/GMT+9', 'Etc'),
    ('Etc/GMT-0', 'Etc'),
    ('Etc/GMT-1', 'Etc'),
    ('Etc/GMT-10', 'Etc'),
    ('Etc/GMT-11', 'Etc'),
    ('Etc/GMT-12', 'Etc'),
    ('Etc/GMT-13', 'Etc'),
    ('Etc/GMT-14', 'Etc'),
    ('Etc/GMT-2', 'Etc'),
    ('Etc/GMT-3', 'Etc'),
    ('Etc/GMT-4', 'Etc'),
    ('Etc/GMT-5', 'Etc'),
    ('Etc/GMT-6', 'Etc'),
    ('Etc/GMT-7', 'Etc'),
    ('Etc/GMT-8', 'Etc'),
    ('Etc/GMT-9', 'Etc'),
    ('Etc/GMT0', 'Etc'),
    ('Etc/Greenwich', 'Etc'),
    ('Etc/UCT', 'Etc'),
    ('Etc/Universal', 'Etc'),
    ('Etc/UTC', 'Etc'),
    ('Etc/Zulu', 'Etc'),
    ('Europe/Amsterdam', 'Europe'),
    ('Europe/Andorra', 'Europe'),
    ('Europe/Astrakhan', 'Europe'),
    ('Europe/Athens', 'Europe'),
    ('Europe/Belfast', 'Europe'),
    ('Europe/Belgrade', 'Europe'),
    ('Europe/Berlin', 'Europe'),
    ('Europe/Bratislava', 'Europe'),
    ('Europe/Brussels', 'Europe'),
    ('Europe/Bucharest', 'Europe'),
    ('Europe/Budapest', 'Europe'),
    ('Europe/Busingen', 'Europe'),
    ('Europe/Chisinau', 'Europe'),
    ('Europe/Copenhagen', 'Europe'),
    ('Europe/Dublin', 'Europe'),
    ('Europe/Gibraltar', 'Europe'),
    ('Europe/Guernsey', 'Europe'),
    ('Europe/Helsinki', 'Europe'),
    ('Europe/Isle_of_Man', 'Europe'),
    ('Europe/Istanbul', 'Europe'),
    ('Europe/Jersey', 'Europe'),
    ('Europe/Kaliningrad', 'Europe'),
    ('Europe/Kiev', 'Europe'),
    ('Europe/Kirov', 'Europe'),
    ('Europe/Lisbon', 'Europe'),
    ('Europe/Ljubljana', 'Europe'),
    ('Europe/London', 'Europe'),
    ('Europe/Luxembourg', 'Europe'),
    ('Europe/Madrid', 'Europe'),
    ('Europe/Malta', 'Europe'),
    ('Europe/Mariehamn', 'Europe'),
    ('Europe/Minsk', 'Europe'),
    ('Europe/Monaco', 'Europe'),
    ('Europe/Moscow', 'Europe'),
    ('Europe/Nicosia', 'Europe'),
    ('Europe/Oslo', 'Europe'),
    ('Europe/Paris', 'Europe'),
    ('Europe/Podgorica', 'Europe'),
    ('Europe/Prague', 'Europe'),
    ('Europe/Riga', 'Europe'),
    ('Europe/Rome', 'Europe'),
    ('Europe/Samara', 'Europe'),
    ('Europe/San_Marino', 'Europe'),
    ('Europe/Sarajevo', 'Europe'),
    ('Europe/Saratov', 'Europe'),
    ('Europe/Simferopol', 'Europe'),
    ('Europe/Skopje', 'Europe'),
    ('Europe/Sofia', 'Europe'),
    ('Europe/Stockholm', 'Europe'),
    ('Europe/Tallinn', 'Europe'),
    ('Europe/Tirane', 'Europe'),
    ('Europe/Tiraspol', 'Europe'),
    ('Europe/Ulyanovsk', 'Europe'),
    ('Europe/Uzhgorod', 'Europe'),
    ('Europe/Vaduz', 'Europe'),
    ('Europe/Vatican', 'Europe'),
    ('Europe/Vienna', 'Europe'),
    ('Europe/Vilnius', 'Europe'),
    ('Europe/Volgograd', 'Europe'),
    ('Europe/Warsaw', 'Europe'),
    ('Europe/Zagreb', 'Europe'),
    ('Europe/Zaporozhye', 'Europe'),
    ('Europe/Zurich', 'Europe'),
    ('GB', 'GB'),
    ('GB-Eire', 'GB-Eire'),
    ('GMT', 'GMT'),
    ('GMT0', 'GMT0'),
    ('Greenwich', 'Greenwich'),
    ('Hongkong', 'Hongkong'),
    ('Iceland', 'Iceland'),
    ('Indian/Antananarivo', 'Indian'),
    ('Indian/Chagos', 'Indian'),
    ('Indian/Christmas', 'Indian'),
    ('Indian/Cocos', 'Indian'),
    ('Indian/Comoro', 'Indian'),
    ('Indian/Kerguelen', 'Indian'),
    ('Indian/Mahe', 'Indian'),
    ('Indian/Maldives', 'Indian'),
    ('Indian/Mauritius', 'Indian'),
    ('Indian/Mayotte', 'Indian'),
    ('Indian/Reunion', 'Indian'),
    ('Iran', 'Iran'),
    ('Israel', 'Israel'),
    ('Jamaica', 'Jamaica'),
    ('Japan', 'Japan'),
    ('Kwajalein', 'Kwajalein'),
    ('Libya', 'Libya'),
    ('MET', 'MET'),
    ('Mexico/BajaNorte', 'Mexico'),
    ('Mexico/BajaSur', 'Mexico'),
    ('Mexico/General', 'Mexico'),
    ('MST7MDT', 'MST7MDT'),
    ('Navajo', 'Navajo'),
    ('NZ', 'NZ'),
    ('NZ-CHAT', 'NZ-CHAT'),
    ('Pacific/Apia', 'Pacific'),
    ('Pacific/Auckland', 'Pacific'),
    ('Pacific/Bougainville', 'Pacific'),
    ('Pacific/Chatham', 'Pacific'),
    ('Pacific/Chuuk', 'Pacific'),
    ('Pacific/Easter', 'Pacific'),
    ('Pacific/Efate', 'Pacific'),
    ('Pacific/Enderbury', 'Pacific'),
    ('Pacific/Fakaofo', 'Pacific'),
    ('Pacific/Fiji', 'Pacific'),
    ('Pacific/Funafuti', 'Pacific'),
    ('Pacific/Galapagos', 'Pacific'),
    ('Pacific/Gambier', 'Pacific'),
    ('Pacific/Guadalcanal', 'Pacific'),
    ('Pacific/Guam', 'Pacific'),
    ('Pacific/Honolulu', 'Pacific'),
    ('Pacific/Johnston', 'Pacific'),
    ('Pacific/Kiritimati', 'Pacific'),
    ('Pacific/Kosrae', 'Pacific'),
    ('Pacific/Kwajalein', 'Pacific'),
    ('Pacific/Majuro', 'Pacific'),
    ('Pacific/Marquesas', 'Pacific'),
    ('Pacific/Midway', 'Pacific'),
    ('Pacific/Nauru', 'Pacific'),
    ('Pacific/Niue', 'Pacific'),
    ('Pacific/Norfolk', 'Pacific'),
    ('Pacific/Noumea', 'Pacific'),
    ('Pacific/Pago_Pago', 'Pacific'),
    ('Pacific/Palau', 'Pacific'),
    ('Pacific/Pitcairn', 'Pacific'),
    ('Pacific/Pohnpei', 'Pacific'),
    ('Pacific/Ponape', 'Pacific'),
    ('Pacific/Port_Moresby', 'Pacific'),
    ('Pacific/Rarotonga', 'Pacific'),
    ('Pacific/Saipan', 'Pacific'),
    ('Pacific/Samoa', 'Pacific'),
    ('Pacific/Tahiti', 'Pacific'),
    ('Pacific/Tarawa', 'Pacific'),
    ('Pacific/Tongatapu', 'Pacific'),
    ('Pacific/Truk', 'Pacific'),
    ('Pacific/Wake', 'Pacific'),
    ('Pacific/Wallis', 'Pacific'),
    ('Pacific/Yap', 'Pacific'),
    ('Poland', 'Poland'),
    ('Portugal', 'Portugal'),
    ('PRC', 'PRC'),
    ('PST8PDT', 'PST8PDT'),
    ('ROK', 'ROK'),
    ('Singapore', 'Singapore'),
    ('SystemV/AST4', 'SystemV'),
    ('SystemV/AST4ADT', 'SystemV'),
    ('SystemV/CST6', 'SystemV'),
    ('SystemV/CST6CDT', 'SystemV'),
    ('SystemV/EST5', 'SystemV'),
    ('SystemV/EST5EDT', 'SystemV'),
    ('SystemV/HST10', 'SystemV'),
    ('SystemV/MST7', 'SystemV'),
    ('SystemV/MST7MDT', 'SystemV'),
    ('SystemV/PST8', 'SystemV'),
    ('SystemV/PST8PDT', 'SystemV'),
    ('SystemV/YST9', 'SystemV'),
    ('SystemV/YST9YDT', 'SystemV'),
    ('Turkey', 'Turkey'),
    ('UCT', 'UCT'),
    ('Universal', 'Universal'),
    ('US/Alaska', 'US'),
    ('US/Aleutian', 'US'),
    ('US/Arizona', 'US'),
    ('US/Central', 'US'),
    ('US/East-Indiana', 'US'),
    ('US/Eastern', 'US'),
    ('US/Hawaii', 'US'),
    ('US/Indiana-Starke', 'US'),
    ('US/Michigan', 'US'),
    ('US/Mountain', 'US'),
    ('US/Pacific', 'US'),
    ('US/Pacific-New', 'US'),
    ('US/Samoa', 'US'),
    ('UTC', 'UTC'),
    ('W-SU', 'W-SU'),
    ('WET', 'WET'),
    ('Zulu', 'Zulu')
    ON CONFLICT (time_zone, short_zone) DO NOTHING;

COMMIT;