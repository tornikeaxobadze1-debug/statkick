CREATE TABLE IF NOT EXISTS "countries" (
	"id" INTEGER,
	"name" VARCHAR NOT NULL,
	"code" VARCHAR,
	"flag_url" VARCHAR,
	PRIMARY KEY("id")
);

CREATE TABLE IF NOT EXISTS "divisions" (
	"id" INTEGER,
	"name" VARCHAR NOT NULL,
	"country_id" INTEGER,
	"logo_url" VARCHAR,
	PRIMARY KEY("id"),
	FOREIGN KEY ("country_id") REFERENCES "countries"("id")
	ON UPDATE NO ACTION ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS "teams" (
	"id" INTEGER,
	"name" VARCHAR NOT NULL,
	"short_code" VARCHAR,
	"division_id" INTEGER,
	"country_id" INTEGER,
	"logo_url" VARCHAR,
	PRIMARY KEY("id"),
	FOREIGN KEY ("division_id") REFERENCES "divisions"("id")
	ON UPDATE NO ACTION ON DELETE SET NULL,
	FOREIGN KEY ("country_id") REFERENCES "countries"("id")
	ON UPDATE NO ACTION ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS "players" (
	"id" INTEGER,
	"first_name" VARCHAR NOT NULL,
	"last_name" VARCHAR NOT NULL,
	"position" VARCHAR,
	"date_of_birth" DATE,
	"country_id" INTEGER,
	"photo_url" VARCHAR,
	PRIMARY KEY("id"),
	FOREIGN KEY ("country_id") REFERENCES "countries"("id")
	ON UPDATE NO ACTION ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS "team_player" (
	"id" INTEGER,
	"team_id" INTEGER NOT NULL,
	"player_id" INTEGER NOT NULL,
	"season" VARCHAR,
	"shirt_number" INTEGER,
	"is_current" BOOLEAN DEFAULT 1,
	PRIMARY KEY("id"),
	FOREIGN KEY ("team_id") REFERENCES "teams"("id")
	ON UPDATE NO ACTION ON DELETE CASCADE,
	FOREIGN KEY ("player_id") REFERENCES "players"("id")
	ON UPDATE NO ACTION ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS "matches" (
	"id" INTEGER,
	"division_id" INTEGER NOT NULL,
	"home_team_id" INTEGER NOT NULL,
	"away_team_id" INTEGER NOT NULL,
	"home_score" INTEGER DEFAULT 0,
	"away_score" INTEGER DEFAULT 0,
	"status" VARCHAR DEFAULT 'SCHEDULED',
	"match_date" DATETIME NOT NULL,
	PRIMARY KEY("id"),
	FOREIGN KEY ("division_id") REFERENCES "divisions"("id")
	ON UPDATE NO ACTION ON DELETE CASCADE,
	FOREIGN KEY ("home_team_id") REFERENCES "teams"("id")
	ON UPDATE NO ACTION ON DELETE CASCADE,
	FOREIGN KEY ("away_team_id") REFERENCES "teams"("id")
	ON UPDATE NO ACTION ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS "player_stats" (
	"id" INTEGER,
	"player_id" INTEGER NOT NULL,
	"match_id" INTEGER NOT NULL,
	"goals" INTEGER DEFAULT 0,
	"assists" INTEGER DEFAULT 0,
	"yellow_cards" INTEGER DEFAULT 0,
	"red_cards" INTEGER DEFAULT 0,
	"minutes_played" INTEGER DEFAULT 0,
	"rating" REAL,
	PRIMARY KEY("id"),
	FOREIGN KEY ("player_id") REFERENCES "players"("id")
	ON UPDATE NO ACTION ON DELETE CASCADE,
	FOREIGN KEY ("match_id") REFERENCES "matches"("id")
	ON UPDATE NO ACTION ON DELETE CASCADE
);
