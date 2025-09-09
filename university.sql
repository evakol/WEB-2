drop database university;
create database university;
use university;

create table users (
ID INT PRIMARY KEY AUTO_INCREMENT,
username VARCHAR(50) UNIQUE NOT NULL,
password VARCHAR(50) NOT NULL,
name VARCHAR (50) NOT NULL,
surname VARCHAR(50) NOT NULL,
role ENUM ( 'Student','Professor', 'Secretary') NOT NULL
);

create table students(
ID INT PRIMARY KEY AUTO_INCREMENT,
user_ID INT NOT NULL,
AM VARCHAR(7) UNIQUE NOT NULL,
street VARCHAR(50),
street_num INT(10),
city VARCHAR(50),
postcode INT(10),
email VARCHAR(50)UNIQUE,
mobile_phone INT(10),
landline_phone INT(10),
FOREIGN KEY (user_ID) REFERENCES users(ID) ON DELETE CASCADE
);

create table professors(
ID INT PRIMARY KEY AUTO_INCREMENT,
user_ID INT NOT NULL,
office_num INT(10),
email VARCHAR(50) UNIQUE,
phone INT(10),
FOREIGN KEY (user_ID) REFERENCES users(ID) ON DELETE CASCADE
);

CREATE TABLE secretary(
ID INT PRIMARY KEY AUTO_INCREMENT,
user_id INT NOT NULL,
email VARCHAR(50) UNIQUE,
phone INT(10),
FOREIGN KEY (user_id) REFERENCES users(ID) ON DELETE CASCADE
);

CREATE TABLE diplwmatiki (
id_diplwm INT PRIMARY KEY AUTO_INCREMENT,
title TEXT NOT NULL,
description TEXT NOT NULL,
descr_file LONGBLOB NOT NULL,
st_file LONGBLOB,
student INT,
professor INT NOT NUll,
exam_1 INT,
exam_2 INT,
status ENUM ('Ypo Anathesi', 'Energi', 'Ypo Eksetasi', 'Peratomeni', 'Akyromeni') NOT NULL,
starting_date DATE,
cancel_num VARCHAR(50),
cancel_year YEAR,
cancel_reason TEXT,
lib_link VARCHAR(50),
app_num INT,
app_year YEAR,
present_date DATE,
present_time TIME,
present_venue VARCHAR(255),
presentation_announcement TEXT,
FOREIGN KEY (professor) REFERENCES professors(ID) ON DELETE CASCADE,
FOREIGN KEY (student) REFERENCES students(ID) ON DELETE CASCADE
);

CREATE TABLE examiners(
diplwm_id INT,
exam_id INT,
invitation_date DATE,
response_date DATE,
status ENUM('Energi' , 'Apodexthike', 'Aporifthike', 'Akirwmeni') NOT NULL,
PRIMARY KEY (diplwm_id,exam_id),
FOREIGN KEY (diplwm_id) REFERENCES diplwmatiki(id_diplwm) ON DELETE CASCADE,
FOREIGN KEY (exam_id) REFERENCES professors(ID) ON DELETE CASCADE
);

CREATE TABLE notes(
note_id INT PRIMARY KEY AUTO_INCREMENT,
diplwm_id INT NOT NULL,
professor INT NOT NULL,
notes VARCHAR(300),
FOREIGN KEY (diplwm_id) REFERENCES diplwmatiki(id_diplwm) ON DELETE CASCADE,
FOREIGN KEY (professor) REFERENCES professors(ID) ON DELETE CASCADE
);

create table grades(
diplwm_id INT NOT NULL,
final_grade DECIMAL(4,2),
grade1_1 DECIMAL(4,2),
grade1_2 DECIMAL(4,2),
grade1_3 DECIMAL(4,2),
grade1_4 DECIMAL(4,2),
grade2_1 DECIMAL(4,2),
grade2_2 DECIMAL(4,2),
grade2_3 DECIMAL(4,2),
grade2_4 DECIMAL(4,2),
grade3_1 DECIMAL(4,2),
grade3_2 DECIMAL(4,2),
grade3_3 DECIMAL(4,2),
grade3_4 DECIMAL(4,2),
FOREIGN KEY (diplwm_id) REFERENCES diplwmatiki(id_diplwm) ON DELETE CASCADE
);

CREATE TABLE diplwm_link(
id INT AUTO_INCREMENT PRIMARY KEY,
diplwm_id INT,
link_url VARCHAR(255),
description VARCHAR(100),
FOREIGN KEY (diplwm_id) REFERENCES diplwmatiki(id_diplwm) ON DELETE CASCADE
);

CREATE TABLE secretary_action(
id INT PRIMARY KEY AUTO_INCREMENT,
diplwm_id INT NOT NULL,
secret_id INT NOT NULL,
prev_status ENUM ('Energi', 'Ypo Eksetasi') NOT NUll,
curr_status ENUM ('Akyromeni', 'Peratomeni') NOT NULL,
cancel_reason TEXT,
gs_date DATE,
FOREIGN KEY (diplwm_id) REFERENCES diplwmatiki(id_diplwm) ON DELETE CASCADE,
FOREIGN KEY (secret_id) REFERENCES secretary(ID) ON DELETE CASCADE
);