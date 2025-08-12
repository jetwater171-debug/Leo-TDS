PRAGMA foreign_keys = off;
BEGIN IMMEDIATE;
CREATE TABLE campaigns
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	name TEXT NOT NULL,
	settings TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS clicks (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	campaign_id INTEGER,
	time INTEGER NOT NULL,
	ip TEXT NOT NULL,
	country TEXT,
	lang TEXT,
	os TEXT,
	osver REAL,
	device TEXT,
	brand TEXT,
	model TEXT,
	isp TEXT,
	client TEXT,
	clientver REAL,
	ua TEXT,
	subid TEXT NOT NULL,
	preland TEXT,
	land TEXT,
	params TEXT,
	leaddata TEXT,
	lpclick BOOLEAN,
	status TEXT,
	cost NUMERIC DEFAULT 0,
	payout NUMERIC DEFAULT 0,
	FOREIGN KEY (
        campaign_id
    )
    REFERENCES campaigns (id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_campid ON clicks (campaign_id);
CREATE INDEX IF NOT EXISTS idx_time ON clicks (time);
CREATE INDEX IF NOT EXISTS idx_camp_time ON clicks (campaign_id,time);

CREATE INDEX IF NOT EXISTS idx_country ON clicks (country);
CREATE INDEX IF NOT EXISTS idx_lang ON clicks (lang);
CREATE INDEX IF NOT EXISTS idx_os ON clicks (os);
CREATE INDEX IF NOT EXISTS idx_osver ON clicks (osver);
CREATE INDEX IF NOT EXISTS idx_brand ON clicks (brand);
CREATE INDEX IF NOT EXISTS idx_model ON clicks (model);
CREATE INDEX IF NOT EXISTS idx_device ON clicks (device);
CREATE INDEX IF NOT EXISTS idx_isp ON clicks (isp);
CREATE INDEX IF NOT EXISTS idx_client ON clicks (client);
CREATE INDEX IF NOT EXISTS idx_clientver ON clicks (clientver);
CREATE INDEX IF NOT EXISTS idx_preland ON clicks (preland);
CREATE INDEX IF NOT EXISTS idx_land ON clicks (land);
CREATE INDEX IF NOT EXISTS idx_status ON clicks (status);

CREATE TABLE IF NOT EXISTS blocked (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	campaign_id INTEGER,
	time INTEGER NOT NULL,
	ip TEXT NOT NULL,
	country TEXT,
	lang TEXT,
	os TEXT,
	osver REAL,
	device TEXT,
	brand TEXT,
	model TEXT,
	isp TEXT,
	client TEXT,
	clientver REAL,
	ua TEXT,
	params TEXT,
	reason TEXT,
	FOREIGN KEY (
        campaign_id
    )
    REFERENCES campaigns (id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_bcamp_time ON blocked (campaign_id,time);

CREATE TABLE IF NOT EXISTS trafficback (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	time INTEGER NOT NULL,
	ip TEXT NOT NULL,
	country TEXT,
	lang TEXT,
	os TEXT,
	osver REAL,
	device TEXT,
	brand TEXT,
	model TEXT,
	isp TEXT,
	client TEXT,
	clientver REAL,
	ua TEXT,
	params TEXT
);
CREATE INDEX IF NOT EXISTS idx_tbtime ON trafficback (time);

CREATE TABLE IF NOT EXISTS common (
    settings TEXT
);
COMMIT TRANSACTION;
PRAGMA foreign_keys = on;
PRAGMA journal_mode = wal;
PRAGMA synchronous = normal;