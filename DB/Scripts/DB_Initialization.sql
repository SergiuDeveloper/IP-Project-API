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
    Title				VARCHAR(64)		NOT NULL,
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
	ID 					INT 		PRIMARY KEY		AUTO_INCREMENT,
	Newsfeed_Post_ID	INT							REFERENCES Newsfeed_Posts.ID,
    Newsfeed_Tag_ID		INT							REFERENCES Newsfeed_Tags.ID,
    
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
	
	UNIQUE KEY (
		Institution_ID,
		Address_ID
	)
);

DROP TABLE IF EXISTS Institution_Rights;
CREATE TABLE Institution_Rights (
	ID										INT							PRIMARY KEY		AUTO_INCREMENT,
	Can_Modify_Institution					BOOLEAN			NOT NULL,
	Can_Delete_Institution					BOOLEAN			NOT NULL,
	Can_Add_Members							BOOLEAN			NOT NULL,
	Can_Remove_Members						BOOLEAN			NOT NULL,
	Can_Change_Members_Rights				BOOLEAN			NOT NULL,
	Can_Upload_Documents					BOOLEAN			NOT NULL,
	Can_Preview_Uploaded_Documents			BOOLEAN			NOT NULL,
	Can_Remove_Uploaded_Documents			BOOLEAN			NOT NULL,
	Can_Send_Documents						BOOLEAN			NOT NULL,
	Can_Preview_Received_Documents			BOOLEAN			NOT NULL,
	Can_Preview_Specific_Received_Document	BOOLEAN			NOT NULL,
	Can_Remove_Received_Documents			BOOLEAN			NOT NULL,
	Can_Download_Documents					BOOLEAN			NOT NULL,
	
	UNIQUE KEY (
		Can_Modify_Institution,
		Can_Delete_Institution,
		Can_Add_Members,
		Can_Remove_Members,
		Can_Change_Members_Rights,
		Can_Upload_Documents,
		Can_Preview_Uploaded_Documents,
		Can_Remove_Uploaded_Documents,
		Can_Send_Documents,
		Can_Preview_Received_Documents,
		Can_Preview_Specific_Received_Document,
		Can_Remove_Received_Documents,
		Can_Download_Documents
	)
);

DROP TABLE IF EXISTS Institution_Members;
CREATE TABLE Institution_Members (
	ID							INT								PRIMARY KEY		AUTO_INCREMENT,
	Institution_ID				INT					NOT NULL				REFERENCES Institutions.ID,
	User_ID						INT					NOT NULL				REFERENCES Users.ID,
	Institution_Right_ID		INT					NOT NULL				REFERENCES Institution_Rights.ID,
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