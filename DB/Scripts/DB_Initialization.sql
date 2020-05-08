DROP SCHEMA Fiscal_Documents_EDI_Live;
CREATE SCHEMA Fiscal_Documents_EDI_Live;
USE Fiscal_Documents_EDI_Live;

CREATE TABLE Users (
	ID 					INT 						PRIMARY KEY		AUTO_INCREMENT,
    Email				VARCHAR(256)	NOT NULL,
	Hashed_Password 	VARCHAR(64) 	NOT NULL,
	First_Name			VARCHAR(64)		NOT NULL,
	Last_Name			VARCHAR(64)		NOT NULL,
	Is_Active			BOOLEAN			NOT NULL 					DEFAULT FALSE,
	DateTime_Created	DATETIME			NULL,
	DateTime_Modified	DATETIME			NULL,
    
    UNIQUE KEY (
		Email
	)
);

CREATE TABLE Administrators (
	ID			INT					PRIMARY KEY		AUTO_INCREMENT,
    Users_ID	INT 	NOT NULL,
    
    CONSTRAINT fk_Users_ID FOREIGN KEY (Users_ID) REFERENCES Users(ID) ON DELETE CASCADE,
    
    UNIQUE KEY (
		Users_ID
	)
);

CREATE TABLE User_Activation_Keys (
	ID 					INT 						PRIMARY KEY		AUTO_INCREMENT,
	User_ID				INT				NOT NULL,
	Unique_Key			VARCHAR(64)		NOT NULL,
	DateTime_Created	DATETIME			NULL,
	DateTime_Used		DATETIME			NULL,
    
    CONSTRAINT fk_User_ID FOREIGN KEY (User_ID) REFERENCES Users(ID) ON DELETE CASCADE,
    
    UNIQUE KEY (
		User_ID
	),
    UNIQUE KEY (
		Unique_key
	)
);

CREATE TABLE Newsfeed_Posts (
	ID 					INT 						PRIMARY KEY		AUTO_INCREMENT,
    Title				VARCHAR(64)		NOT NULL,
    Content				VARCHAR(256)	NOT NULL,
    URL					VARCHAR(2048)	NOT NULL,
    DateTime_Created	DATETIME			NULL,
    
    UNIQUE KEY (
		Title
	)
);

CREATE TABLE Newsfeed_Tags (
	ID 		INT 						PRIMARY KEY		AUTO_INCREMENT,
	Title	VARCHAR (64)	NOT NULL,
    
    UNIQUE KEY (
		Title
	)
);

CREATE TABLE Newsfeed_Posts_Tags_Assignations (
	ID 					INT 				PRIMARY KEY		AUTO_INCREMENT,
	Newsfeed_Post_ID	INT		NOT NULL,
    Newsfeed_Tag_ID		INT		NOT NULL,
    
    CONSTRAINT fk_Newsfeed_Post_ID FOREIGN KEY (Newsfeed_Post_ID) REFERENCES Newsfeed_Posts(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Newsfeed_Tag_ID FOREIGN KEY (Newsfeed_Tag_ID) REFERENCES Newsfeed_Tags(ID) ON DELETE CASCADE,
    
    UNIQUE KEY (
		Newsfeed_Post_ID,
        Newsfeed_Tag_ID
    )
);

CREATE TABLE Institution_Contact_Information (
	ID				INT							PRIMARY KEY		AUTO_INCREMENT,
    Email			VARCHAR(256)	NOT NULL,
    Phone_Number	VARCHAR(16)			NULL,
    Fax				VARCHAR(16)			NULL,
    
    UNIQUE KEY (
		Email,
        Phone_Number,
        Fax
    )
);

CREATE TABLE Institutions (
	ID										INT							PRIMARY KEY		AUTO_INCREMENT,
	Name									VARCHAR(64)		NOT NULL,
    CIF										VARCHAR(12)		NOT NULL,
    Institution_Contact_Information_ID		INT				NOT NULL,
	DateTime_Created						DATETIME			NULL,
	DateTime_Modified						DATETIME			NULL,
    
    CONSTRAINT fk_Institutions_Institution_Contact_Information_ID FOREIGN KEY (Institution_Contact_Information_ID) REFERENCES Institution_Contact_Information(ID) ON DELETE CASCADE,
    
    UNIQUE KEY (
		Name
	),
    UNIQUE KEY (
		CIF
	)
);

CREATE TABLE Addresses (
	ID					INT								PRIMARY KEY		AUTO_INCREMENT,
	Country				VARCHAR(64)			NOT NULL,
	Region				VARCHAR(64)			NOT NULL,
	City				VARCHAR(64)			NOT NULL,
	Street				VARCHAR(64)			NOT NULL,
	Number				INT					NOT NULL,
	Building			VARCHAR(64)				NULL,
	Floor				INT						NULL,
	Apartment			INT						NULL,
    
    UNIQUE KEY (
		Country,
        Region,
        City,
        Street,
        Number,
        Building,
        Floor,
        Apartment
	)
);

CREATE TABLE Institution_Addresses_List (
	ID					INT							PRIMARY KEY		AUTO_INCREMENT,
	Institution_ID		INT				NOT NULL,
	Address_ID			INT				NOT NULL,
	Is_Main_Address		BOOLEAN			NOT NULL,
    
    CONSTRAINT fk_Institution_ID FOREIGN KEY (Institution_ID) REFERENCES Institutions(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Address_ID FOREIGN KEY (Address_ID) REFERENCES Addresses(ID) ON DELETE CASCADE,
    
	UNIQUE KEY (
		Institution_ID,
		Address_ID
	)
);

DELIMITER //
CREATE PROCEDURE sp_Unique_Institution_Main_Address_Validation (
	is_main_address	BOOLEAN,
    institution_id	INT
)
BEGIN
	IF is_main_address = TRUE THEN
		SET @institution_main_addresses_count = (SELECT COUNT(*) FROM Institution_Addresses_List WHERE Institution_Addresses_List.Institution_ID = institution_id AND Institution_Addresses_List.Is_Main_Address = TRUE);
        IF @institution_main_addresses_count > 0 THEN
			SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An institution cannot have two main addresses';
		END IF;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER t_Institution_Addresses_List_Before_Insert BEFORE INSERT ON Institution_Addresses_List
FOR EACH ROW
BEGIN
	CALL sp_Unique_Institution_Main_Address_Validation(NEW.Is_Main_Address, NEW.Institution_ID);
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER t_Institution_Addresses_List_Before_Update BEFORE UPDATE ON Institution_Addresses_List
FOR EACH ROW
BEGIN
	CALL sp_Unique_Institution_Main_Address_Validation(NEW.Is_Main_Address, NEW.Institution_ID);
END //
DELIMITER ;

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

DELIMITER //
CREATE PROCEDURE sp_Institution_Rights_Row_Validation (
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

CREATE TABLE Institution_Roles (
	ID						INT							PRIMARY KEY		AUTO_INCREMENT,
    Institution_ID			INT 			NOT NULL,
    Institution_Rights_ID 	INT 			NOT NULL,
    Title					VARCHAR(64)		NOT NULL,
    
    CONSTRAINT fk_Institution_ID_Roles FOREIGN KEY (Institution_ID) REFERENCES Institutions(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Institution_Rights_ID FOREIGN KEY (Institution_Rights_ID) REFERENCES Institution_Rights(ID) ON DELETE CASCADE,
    
    UNIQUE KEY (
		Institution_ID,
        Institution_Rights_ID
    ),
    UNIQUE KEY (
		Institution_ID,
        Title
    )
);

CREATE TABLE Institution_Members (
	ID							INT								PRIMARY KEY		AUTO_INCREMENT,
	Institution_ID				INT					NOT NULL,
	User_ID						INT					NOT NULL,
	Institution_Roles_ID		INT					NOT NULL,
	DateTime_Added				DATETIME				NULL,
	DateTime_Modified_Rights	DATETIME				NULL,
    
    CONSTRAINT fk_Institution_ID_Members FOREIGN KEY (Institution_ID) REFERENCES Institutions(ID) ON DELETE CASCADE,
    CONSTRAINT fk_User_ID_Members FOREIGN KEY (User_ID) REFERENCES Users(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Institution_Roles_ID FOREIGN KEY (Institution_Roles_ID) REFERENCES Institution_Roles(ID) ON DELETE CASCADE,
	
	UNIQUE KEY (
		Institution_ID,
		User_ID
	)
);

CREATE TABLE Cloud_Files (
	ID										INT								PRIMARY KEY		AUTO_INCREMENT,
	Path									VARCHAR(4096)		NOT NULL,
	Sender_Institution_ID					INT					NOT NULL,
	Receiver_Institution_ID					INT					NOT NULL,
	Sender_User_ID							INT					NOT NULL,
	Receiver_User_ID						INT					NOT NULL,
	Was_Sent								BOOLEAN				NOT NULL					DEFAULT FALSE,
	Was_Received							BOOLEAN				NOT NULL					DEFAULT FALSE,
	DateTime_Added							DATETIME				NULL,
	DateTime_Received						DATETIME				NULL,
	DateTime_Receiver_Previewed_In_List		DATETIME				NULL,
	DateTime_Receiver_Previewed				DATETIME				NULL,
	DateTime_Receiver_Downloaded			DATETIME				NULL,
    
    CONSTRAINT fk_Sender_Institution_ID FOREIGN KEY (Sender_Institution_ID) REFERENCES Institutions(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Receiver_Institution_ID FOREIGN KEY (Receiver_Institution_ID) REFERENCES Institutions(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Sender_User_ID FOREIGN KEY (Sender_User_ID) REFERENCES Users(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Receiver_User_ID FOREIGN KEY (Receiver_User_ID) REFERENCES Users(ID) ON DELETE CASCADE
);

CREATE TABLE Document_Types (
	ID		INT				PRIMARY KEY		AUTO_INCREMENT,
    Title 	VARCHAR(64)		NOT NULL,
    
    UNIQUE KEY (
		Title
    )
);

CREATE TABLE Documents (
	ID 							INT						PRIMARY KEY		AUTO_INCREMENT,
    Date_Created 				DATETIME 	NOT NULL,
    Creator_User_ID				INT				NULL,
    Sender_User_ID 				INT 			NULL,
    Sender_Institution_ID 		INT 		NOT NULL,
    Sender_Address_ID 			INT 			NULL,
    Date_Sent 					DATETIME 		NULL,
    Is_Sent 					BOOLEAN 	NOT NULL,
    Receiver_User_ID 			INT 			NULL,
    Receiver_Institution_ID 	INT 			NULL,
    Receiver_Address_ID 		INT 			NULL,
	Document_Types_ID			INT			NOT NULL,
    
    CONSTRAINT fk_Creator_User_ID FOREIGN KEY (Creator_User_ID) REFERENCES Users(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Sender_User_ID_Documents FOREIGN KEY (Sender_User_ID) REFERENCES Users(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Sender_Institution_ID_Documents FOREIGN KEY (Sender_Institution_ID) REFERENCES Institutions(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Sender_Address_ID FOREIGN KEY (Sender_Address_ID) REFERENCES Addresses(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Receiver_User_ID_Documents FOREIGN KEY (Receiver_User_ID) REFERENCES Users(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Receiver_Institution_ID_Documents FOREIGN KEY (Receiver_Institution_ID) REFERENCES Institutions(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Receiver_Address_ID FOREIGN KEY (Receiver_Address_ID) REFERENCES Addresses(ID) ON DELETE CASCADE,
	CONSTRAINT fk_Document_Types_ID FOREIGN KEY (Document_Types_ID) REFERENCES Document_Types(ID) ON DELETE CASCADE
);

CREATE TABLE Payment_Methods (
	ID		INT							PRIMARY KEY		AUTO_INCREMENT,
    Title	VARCHAR(64)		NOT NULL,
    
    UNIQUE KEY (
		Title
	)
);

CREATE TABLE Receipts (
	ID					INT							PRIMARY KEY		AUTO_INCREMENT,
    Documents_ID		INT 			NOT NULL,
    Invoices_ID			INT					NULL,
    Payment_Number		VARCHAR(64)			NULL,
    Payment_Methods_ID 	INT 				NULL,
    
	CONSTRAINT fk_Documents_ID FOREIGN KEY (Documents_ID) REFERENCES Documents(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Payment_Methods_ID FOREIGN KEY (Payment_Methods_ID) REFERENCES Payment_Methods(ID) ON DELETE CASCADE,
	
    UNIQUE KEY (
		Documents_ID
	),
    UNIQUE KEY (
		Payment_Number
    )
);

CREATE TABLE Invoices (
	ID				INT					PRIMARY KEY		AUTO_INCREMENT,
	Documents_ID	INT 	NOT NULL,
    Receipts_ID		INT			NULL,
    
	CONSTRAINT fk_Documents_ID_Invoices FOREIGN KEY (Documents_ID) REFERENCES Documents(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Receipts_ID FOREIGN KEY (Receipts_ID) REFERENCES Receipts(ID) ON DELETE CASCADE,
    
    UNIQUE KEY (
		Documents_ID
	)
);

ALTER TABLE Receipts ADD CONSTRAINT fk_Invoices_ID FOREIGN KEY (Invoices_ID) REFERENCES Invoices(ID) ON DELETE CASCADE;

CREATE TABLE Currencies (
	ID		INT							PRIMARY KEY		AUTO_INCREMENT,
    Title	VARCHAR(64)		NOT NULL
);

CREATE TABLE Items (
	ID 					INT 						PRIMARY KEY 	AUTO_INCREMENT,
    Product_Number		INT					NULL,
    Title 				VARCHAR(64) 		NULL,
    Description 		VARCHAR(128) 		NULL,
    Value_Before_Tax	FLOAT 			NOT NULL,
    Tax_Percentage		FLOAT			NOT NULL,
    Value_After_Tax		FLOAT			NOT NULL,
    Currencies_ID		INT				NOT NULL,
    
    UNIQUE KEY (
		Product_Number,
		Title,
        Description,
        Value_Before_Tax,
        Tax_Percentage,
        Value_After_Tax
    ),
    
    CONSTRAINT fk_Currencies_ID FOREIGN KEY (Currencies_ID) REFERENCES Currencies(ID) ON DELETE CASCADE
);

CREATE TABLE Document_Items (
	ID 				INT 				PRIMARY KEY 	AUTO_INCREMENT,
    Invoices_ID 	INT 		NULL,
    Receipts_ID		INT 		NULL,
    Items_ID 		INT 	NOT NULL,
    Quantity 		INT 	NOT NULL,
    
    CONSTRAINT fk_Invoices_ID_Document_Items FOREIGN KEY (Invoices_ID) REFERENCES Invoices(ID) ON DELETE CASCADE,
	CONSTRAINT fk_Receipts_ID_Document_Items FOREIGN KEY (Receipts_ID) REFERENCES Receipts(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Items_ID FOREIGN KEY (Items_ID) REFERENCES Items(ID) ON DELETE CASCADE,
    
    UNIQUE KEY (
		Invoices_ID,
        Receipts_ID,
        Items_ID
	)
);

CREATE TABLE Notification_Types (
	ID				INT							PRIMARY KEY		AUTO_INCREMENT,
    Name			VARCHAR(64)		NOT NULL,
    Default_Title	VARCHAR(64)			NULL,
    Default_Content	VARCHAR(256)		NULL,
    
    UNIQUE KEY (
		Name,
        Default_Title,
        Default_Content
	)
);

CREATE TABLE Notifications (
	ID						INT							PRIMARY KEY		AUTO_INCREMENT,
    Institution_ID			INT				NOT NULL,
    Notification_Types_ID	INT 			NOT NULL,
    Title					VARCHAR(64)			NULL,
    Content					VARCHAR(256)		NULL,
    Sender_User_ID			INT					NULL,
    
    CONSTRAINT fk_Institution_ID_Notifications FOREIGN KEY (Institution_ID) REFERENCES Institutions(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Notification_Types_ID FOREIGN KEY (Notification_Types_ID) REFERENCES Notification_Types(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Sender_User_ID_Notifications FOREIGN KEY (Sender_User_ID) REFERENCES Users(ID) ON DELETE CASCADE,
    
    UNIQUE KEY (
		Institution_ID,
        Notification_Types_ID,
        Title,
        Content
    )
);

CREATE TABLE Notification_Subscriptions (
	ID					INT		PRIMARY KEY		AUTO_INCREMENT,
    User_ID				INT		NOT NULL,
    Notification_ID		INT		NOT NULL,
    
    CONSTRAINT fk_User_ID_Subscriptions FOREIGN KEY (User_ID) REFERENCES Users(ID) ON DELETE CASCADE,
    CONSTRAINT fk_Notification_ID FOREIGN KEY (Notification_ID) REFERENCES Notifications(ID) ON DELETE CASCADE,
    
    UNIQUE KEY (
		User_ID,
        Notification_ID
    )
);