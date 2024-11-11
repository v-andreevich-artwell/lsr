CREATE TABLE IF NOT EXISTS buildings (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ACTIVE CHAR(1) DEFAULT 'Y',
    NAME VARCHAR(255) NOT NULL,
    PHOTO_GALLERY TEXT
);

CREATE TABLE IF NOT EXISTS apartments (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ACTIVE VARCHAR(1) DEFAULT 'Y',
    NUMBER INT NOT NULL,
    BUILDING_ID INT,
    STATUS ENUM('for_sale', 'not_for_sale') DEFAULT 'for_sale',
    PRICE DOUBLE,
    DISCOUNT_PRICE DOUBLE,
    HAS_DISCOUNT VARCHAR(1) DEFAULT 'N',
    PHOTO_GALLERY TEXT
);
