-- Stores changesets
CREATE TABLE audits (
	id          char(36)     NOT NULL,
	event       varchar(255) NOT NULL,	-- Create, Update, Delete, etc.
	model       varchar(255) NOT NULL,	-- For PolymorphicBehavior compatibility
	entity_id   char(36)     NOT NULL,	-- For PolymorphicBehavior compatibility
	json_object text         NOT NULL,	-- A snapshot of the complete object at the time of the change
	description text         NULL,
	source_id   varchar(255) NULL, 			-- A value to indicate the source of the change (user id, etc.)
	created     datetime     NOT NULL,
	
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Stores any individual property changes for a given changeset
CREATE TABLE audit_deltas (
	id            char(36)     NOT NULL,
	audit_id  		char(36)     NOT NULL,
	property_name varchar(255) NOT NULL,
	old_value     varchar(255) NULL,
	new_value     varchar(255) NULL,
	
	PRIMARY KEY (id),
	FOREIGN KEY (audit_id)
		REFERENCES audits (id)
			ON DELETE CASCADE
			ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
