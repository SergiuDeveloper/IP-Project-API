DROP TABLE IF EXISTS Users;
CREATE TABLE Users (
	ID 					INT 						PRIMARY KEY		AUTO_INCREMENT,
	Username 			VARCHAR(32) 	NOT NULL 	UNIQUE KEY,
	Hashed_Password 	VARCHAR(64) 	NOT NULL,
	Email				VARCHAR(256)	NOT NULL 	UNIQUE KEY,
	First_Name			VARCHAR(64)		NOT NULL,
	Last_Name			VARCHAR(64)		NOT NULL,
	Is_Active			BOOLEAN			NOT NULL 					DEFAULT FALSE,
	DateTime_Created	DATETIME			NULL,
	DateTime_Modified	DATETIME			NULL
);

DROP TABLE IF EXISTS Administrators;
CREATE TABLE Administrators (
	ID			INT					PRIMARY KEY		AUTO_INCREMENT,
    Users_ID	INT 	NOT NULL 	UNIQUE KEY 		REFERENCES Users.ID
);

DROP TABLE IF EXISTS User_Activation_Keys;
CREATE TABLE User_Activation_Keys (
	ID 					INT 						PRIMARY KEY		AUTO_INCREMENT,
	User_ID				INT				NOT NULL	UNIQUE KEY		REFERENCES Users.ID,
	Unique_Key			VARCHAR(64)		NOT NULL	UNIQUE KEY,
	DateTime_Created	DATETIME			NULL,
	DateTime_Used		DATETIME			NULL
);

DROP TABLE IF EXISTS Newsfeed_Posts;
CREATE TABLE Newsfeed_Posts (
	ID 					INT 						PRIMARY KEY		AUTO_INCREMENT,
    Title				VARCHAR(64)		NOT NULL	UNIQUE KEY,
    Content				VARCHAR(256)	NOT NULL,
    URL					VARCHAR(2048)	NOT NULL,
    DateTime_Created	DATETIME			NULL
);

DROP TABLE IF EXISTS Newsfeed_Tags;
CREATE TABLE Newsfeed_Tags (
	ID 		INT 						PRIMARY KEY		AUTO_INCREMENT,
	Title	VARCHAR (64)	NOT NULL	UNIQUE KEY
);

DROP TABLE IF EXISTS Newsfeed_Posts_Tags_Assignations;
CREATE TABLE Newsfeed_Posts_Tags_Assignations (
	ID 					INT 			PRIMARY KEY		AUTO_INCREMENT,
	Newsfeed_Post_ID	INT	NOT NULL					REFERENCES Newsfeed_Posts.ID,
    Newsfeed_Tag_ID		INT	NOT NULL					REFERENCES Newsfeed_Tags.ID,
    
    UNIQUE KEY (
		Newsfeed_Post_ID,
        Newsfeed_Tag_ID
    )
);

DROP TABLE IF EXISTS Institutions;
CREATE TABLE Institutions (
	ID					INT							PRIMARY KEY		AUTO_INCREMENT,
	Name				VARCHAR(64)		NOT NULL	UNIQUE KEY,
	DateTime_Created	DATETIME			NULL,
	DateTime_Modified	DATETIME			NULL
);

DROP TABLE IF EXISTS Addresses;
CREATE TABLE Addresses (
	ID					INT							PRIMARY KEY		AUTO_INCREMENT,
	Country				VARCHAR(64)			NULL,
	Region				VARCHAR(64)			NULL,
	City				VARCHAR(64)			NULL,
	Street				VARCHAR(64)			NULL,
	Number				INT					NULL,
	Building			VARCHAR(64)			NULL,
	Floor				INT					NULL,
	Apartment			INT					NULL
);

DROP TABLE IF EXISTS Institution_Addresses_List;
CREATE TABLE Institution_Addresses_List (
	ID					INT							PRIMARY KEY		AUTO_INCREMENT,
	Institution_ID		INT				NOT NULL					REFERENCES Institutions.ID,
	Address_ID			INT				NOT NULL					REFERENCES Addresses.ID,
	Is_Main_Address		BOOLEAN			NOT NULL,
    
	UNIQUE KEY (
		Institution_ID,
		Address_ID
	)
);

DROP PROCEDURE IF EXISTS sp_Unique_Institution_Main_Address_Validation;
DELIMITER //
CREATE PROCEDURE sp_Unique_Institution_Main_Address_Validation(
	is_main_address	BOOLEAN,
    institution_id	INT
)
BEGIN
	IF new_row_is_main_address = TRUE THEN
		SET @institution_main_addresses_count = (SELECT COUNT(*) FROM Institution_Addresses_List WHERE Institution_Addresses_List.Institution_ID = institution_id AND Institution_Addresses_List.Is_Main_Address = TRUE);
        IF @institution_main_addresses_count > 0 THEN
			SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An institution cannot have two main addresses';
		END IF;
    END IF;
END //
DELIMITER ;

DROP TRIGGER IF EXISTS t_Institution_Addresses_List_Before_Insert;
DELIMITER //
CREATE TRIGGER t_Institution_Addresses_List_Before_Insert BEFORE INSERT ON Institution_Addresses_List
FOR EACH ROW
BEGIN
	CALL sp_Unique_Institution_Main_Address_Validation(NEW.Is_Main_Address, NEW.Institution_ID);
END //
DELIMITER ;

DROP TRIGGER IF EXISTS t_Institution_Addresses_List_Before_Update;
DELIMITER //
CREATE TRIGGER t_Institution_Addresses_List_Before_Update BEFORE UPDATE ON Institution_Addresses_List
FOR EACH ROW
BEGIN
	CALL sp_Unique_Institution_Main_Address_Validation(NEW.Is_Main_Address, NEW.Institution_ID);
END //
DELIMITER ;

DROP TABLE IF EXISTS Institution_Rights;
CREATE TABLE Institution_Rights (
	ID										INT							PRIMARY KEY		AUTO_INCREMENT,
	Can_Modify_Institution					BOOLEAN			NOT NULL,
	Can_Delete_Institution					BOOLEAN			NOT NULL,
	Can_Add_Members							BOOLEAN			NOT NULL,
	Can_Remove_Members						BOOLEAN			NOT NULL,
	Can_Upload_Documents					BOOLEAN			NOT NULL,
	Can_Preview_Uploaded_Documents			BOOLEAN			NOT NULL,
	Can_Remove_Uploaded_Documents			BOOLEAN			NOT NULL,
	Can_Send_Documents						BOOLEAN			NOT NULL,
	Can_Preview_Received_Documents			BOOLEAN			NOT NULL,
	Can_Preview_Specific_Received_Document	BOOLEAN			NOT NULL,
	Can_Remove_Received_Documents			BOOLEAN			NOT NULL,
	Can_Download_Documents					BOOLEAN			NOT NULL,
    Can_Add_Roles							BOOLEAN			NOT NULL,
    Can_Remove_Roles						BOOLEAN			NOT NULL,
    Can_Modify_Roles						BOOLEAN			NOT NULL,
    Can_Assign_Roles						BOOLEAN			NOT NULL,
    Can_Deassign_Roles						BOOLEAN			NOT NULL
);

DROP PROCEDURE IF EXISTS sp_Institution_Rights_Row_Validation;
DELIMITER //
CREATE PROCEDURE sp_Institution_Rights_Row_Validation(
	can_Modify_Institution						BOOLEAN,
	can_Delete_Institution						BOOLEAN,
	can_Add_Members								BOOLEAN,
	can_Remove_Members							BOOLEAN,
	can_Upload_Documents						BOOLEAN,
	can_Preview_Uploaded_Documents				BOOLEAN,
	can_Remove_Uploaded_Documents				BOOLEAN,
	can_Send_Documents							BOOLEAN,
	can_Preview_Received_Documents				BOOLEAN,
	can_Preview_Specific_Received_Document		BOOLEAN,
	can_Remove_Received_Documents				BOOLEAN,
	can_Download_Documents						BOOLEAN,
	can_Add_Roles								BOOLEAN,
	can_Remove_Roles							BOOLEAN,
	can_Modify_Roles							BOOLEAN,
	can_Assign_Roles							BOOLEAN,
	can_Deassign_Roles							BOOLEAN
)
BEGIN
	SET @institution_rights_row_count = (
		SELECT COUNT(*) FROM Institution_Rights WHERE
			Institution_Rights.Can_Modify_Institution 					= can_Modify_Institution 					AND
			Institution_Rights.Can_Delete_Institution 					= can_Delete_Institution 					AND
			Institution_Rights.Can_Add_Members 							= can_Add_Members							AND
			Institution_Rights.Can_Remove_Members 						= can_Remove_Members 						AND
			Institution_Rights.Can_Upload_Documents 					= can_Upload_Documents 						AND
			Institution_Rights.Can_Preview_Uploaded_Documents 			= can_Preview_Uploaded_Documents 			AND
			Institution_Rights.Can_Remove_Uploaded_Documents 			= can_Remove_Uploaded_Documents 			AND
			Institution_Rights.Can_Send_Documents 						= can_Send_Documents 						AND
			Institution_Rights.Can_Preview_Received_Documents 			= can_Preview_Received_Documents 			AND
			Institution_Rights.Can_Preview_Specific_Received_Document	= can_Preview_Specific_Received_Document	AND
			Institution_Rights.Can_Remove_Received_Documents 			= can_Remove_Received_Documents 			AND
			Institution_Rights.Can_Download_Documents 					= can_Download_Documents 					AND
			Institution_Rights.Can_Add_Roles 							= can_Add_Roles 							AND
			Institution_Rights.Can_Remove_Roles 						= can_Remove_Roles 							AND
			Institution_Rights.Can_Modify_Roles 						= can_Modify_Roles 							AND
			Institution_Rights.Can_Assign_Roles 						= can_Assign_Roles 							AND
			Institution_Rights.Can_Deassign_Roles 						= can_Deassign_Roles
	);
	IF @institution_rights_row_count > 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The requested institution right row already exists';
	END IF;
END //
DELIMITER ;

DROP TRIGGER IF EXISTS t_Institution_Rights_Before_Insert;
DELIMITER //
CREATE TRIGGER t_Institution_Rights_Before_Insert BEFORE INSERT ON Institution_Rights
FOR EACH ROW
BEGIN
	CALL sp_Institution_Rights_Row_Validation(
		NEW.Can_Modify_Institution,
		NEW.Can_Delete_Institution,
		NEW.Can_Add_Members,
		NEW.Can_Remove_Members,
		NEW.Can_Upload_Documents,
		NEW.Can_Preview_Uploaded_Documents,
		NEW.Can_Remove_Uploaded_Documents,
		NEW.Can_Send_Documents,
		NEW.Can_Preview_Received_Documents,
		NEW.Can_Preview_Specific_Received_Document,
		NEW.Can_Remove_Received_Documents,
		NEW.Can_Download_Documents,
		NEW.Can_Add_Roles,
		NEW.Can_Remove_Roles,
		NEW.Can_Modify_Roles,
		NEW.Can_Assign_Roles,
		NEW.Can_Deassign_Roles
	);
END //
DELIMITER ;

DROP TRIGGER IF EXISTS t_Institution_Rights_Before_Update;
DELIMITER //
CREATE TRIGGER t_Institution_Rights_Before_Update BEFORE UPDATE ON Institution_Rights
FOR EACH ROW
BEGIN
	CALL sp_Institution_Rights_Row_Validation(
		NEW.Can_Modify_Institution,
		NEW.Can_Delete_Institution,
		NEW.Can_Add_Members,
		NEW.Can_Remove_Members,
		NEW.Can_Upload_Documents,
		NEW.Can_Preview_Uploaded_Documents,
		NEW.Can_Remove_Uploaded_Documents,
		NEW.Can_Send_Documents,
		NEW.Can_Preview_Received_Documents,
		NEW.Can_Preview_Specific_Received_Document,
		NEW.Can_Remove_Received_Documents,
		NEW.Can_Download_Documents,
		NEW.Can_Add_Roles,
		NEW.Can_Remove_Roles,
		NEW.Can_Modify_Roles,
		NEW.Can_Assign_Roles,
		NEW.Can_Deassign_Roles
	);
END //
DELIMITER ;

DROP TABLE IF EXISTS Institution_Roles;
CREATE TABLE Institution_Roles (
	ID						INT					PRIMARY KEY		AUTO_INCREMENT,
    Institution_ID			INT 	NOT NULL					REFERENCES Institutions.ID,
    Institution_Rights_ID 	INT 	NOT NULL					REFERENCES Institution_Rights.ID,
    Title					VARCHAR(64),
    
    UNIQUE KEY (
		Institution_ID,
        Institution_Rights_ID
    ),
    UNIQUE KEY (
		Institution_ID,
        Title
    )
);

DROP TABLE IF EXISTS Institution_Members;
CREATE TABLE Institution_Members (
	ID							INT								PRIMARY KEY		AUTO_INCREMENT,
	Institution_ID				INT					NOT NULL					REFERENCES Institutions.ID,
	User_ID						INT					NOT NULL					REFERENCES Users.ID,
	Institution_Roles_ID		INT					NOT NULL					REFERENCES Institution_Roles.ID,
	DateTime_Added				DATETIME				NULL,
	DateTime_Modified_Rights	DATETIME				NULL,
	
	UNIQUE KEY (
		Institution_ID,
		User_ID
	)
);

DROP TABLE IF EXISTS Cloud_Files;
CREATE TABLE Cloud_Files (
	ID										INT								PRIMARY KEY		AUTO_INCREMENT,
	Path									VARCHAR(4096)		NOT NULL,
	Sender_Institution_ID					INT					NOT NULL					REFERENCES Institutions.ID,
	Receiver_Institution_ID					INT					NOT NULL					REFERENCES Institutions.ID,
	Sender_User_ID							INT					NOT NULL					REFERENCES Users.ID,
	Receiver_User_ID						INT					NOT NULL					REFERENCES Users.ID,
	Was_Sent								BOOLEAN				NOT NULL					DEFAULT FALSE,
	Was_Received							BOOLEAN				NOT NULL					DEFAULT FALSE,
	DateTime_Added							DATETIME				NULL,
	DateTime_Received						DATETIME				NULL,
	DateTime_Receiver_Previewed_In_List		DATETIME				NULL,
	DateTime_Receiver_Previewed				DATETIME				NULL,
	DateTime_Receiver_Downloaded			DATETIME				NULL
);

DROP TABLE IF EXISTS Receipts;
CREATE TABLE Receipts (
	ID				INT							PRIMARY KEY		AUTO_INCREMENT,
	Title			VARCHAR(64)			NULL,
	Value			INT				NOT NULL,
	Cloud_File_ID	INT				NOT NULL					REFERENCES Cloud_Files.ID
);

DROP TABLE IF EXISTS Invoices;
CREATE TABLE Invoices (
	ID				INT							PRIMARY KEY		AUTO_INCREMENT,
	Title			VARCHAR(64)			NULL,
	Value			INT				NOT NULL,
	Cloud_File_ID	INT				NOT NULL					REFERENCES Cloud_Files.ID
);