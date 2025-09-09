use university;

INSERT INTO users (username, password, name , surname, role) VALUES
('student1_user', 'pass123', 'John', 'Papadopoulos', 'Student'),
('student2_user', 'pass456', 'Maria', 'Georgiou', 'Student'),
('student3_user', 'pass789', 'George', 'Dimitriou', 'Student'),
('student4_user', 'pass101', 'Eleni', 'Konstantinou', 'Student'),
('student5_user', 'pass112', 'Kostas', 'Vlachos', 'Student'),
('student6_user', 'pass131', 'Anna', 'Ioannou', 'Student'),
('student7_user', 'pass141', 'Nikos', 'Adamopoulos', 'Student'),
('student8_user', 'pass151', 'Sophia', 'Nikolaou', 'Student'),
('student9_user', 'pass161', 'Panagiotis', 'Karagiannis', 'Student'),
('student10_user', 'pass171', 'Dimitra', 'Athanasiou', 'Student'),
('prof1_user', 'prof1pass', 'Athanasios', 'Makris', 'Professor'),
('prof2_user', 'prof2pass', 'Eleni', 'Katsarou', 'Professor'),
('prof3_user', 'prof3pass', 'Ioannis', 'Pappas', 'Professor'),
('prof4_user', 'prof4pass', 'Georgia', 'Fotiou', 'Professor'),
('prof5_user', 'prof5pass', 'Sotirios', 'Kouvelis', 'Professor'),
('sec1_user', 'sec1pass', 'Vasiliki', 'Antoniou', 'Secretary'),
('sec2_user', 'sec2pass', 'Christos', 'Papatheodorou', 'Secretary');

INSERT INTO students (user_id, AM, street, street_num, city, postcode, email, mobile_phone, landline_phone) VALUES
(1, '8095345', 'Athanasiou Diakou', 10, 'Patra', 26345, 'john.papadopoulos@mail.gr', 6978541230, 2610123456),
(2, '9234652', 'Pireos', 25, 'Athens', 10435, 'maria.georgiou@mail.gr', 6985214796, 2101234567),
(3, '1942754', 'Ermou', 50, 'Thessaloniki', 54625, 'george.dimitriou@mail.gr', 6945632178, 2310123456),
(4, '7583804', 'Panepistimiou', 15, 'Patra', 26100, 'eleni.konstantinou@mail.gr', 6932145678, 2610987654),
(5, '6623559', 'Ethnikis Aminis', 30, 'Thessaloniki', 54621, 'kostas.vlachos@mail.gr', 6954789632, 2310987654),
(6, '5270253', 'Stournara', 45, 'Athens', 10682, 'anna.ioannou@mail.gr', 6963215874, 2109876543),
(7, '1237567', 'Omonias', 2, 'Patra', 26100, 'nikos.adamopoulos@mail.gr', 6921457896, 2610234567),
(8, '7639453', 'Mitropoleos', 7, 'Thessaloniki', 54622, 'sophia.nikolaou@mail.gr', 6998745632, 2310567890),
(9, '7629864', 'Kalamakiou', 100, 'Athens', 17455, 'panos.karagiannis@mail.gr', 6912345678, 2106543210),
(10, '6824864', 'Egnatia', 120, 'Thessaloniki', 54626, 'dimitra.athanasiou@mail.gr', 6978945612, 2310876543);

INSERT INTO professors (user_id, office_num, email, phone) VALUES
(11, 305, 'athanasios.makris@mail.gr', 2610555123),
(12, 401, 'eleni.katsarou@mail.gr', 2610664567),
(13, 112, 'ioannis.pappas@mail.gr', 2610777890),
(14, 203, 'georgia.fotiou@mail.gr', 2610888901),
(15, 315, 'sotirios.kouvelis@mail.gr', 2610990123);

INSERT INTO secretary (user_id, email, phone) VALUES
(16, 'vasiliki.antoniou@mail.gr', 2610111222),
(17, 'christos.papatheodorou@mail.gr', 2610223333);

INSERT INTO diplwmatiki (title, description, descr_file, st_file, student, professor, exam_1, exam_2, status, starting_date, cancel_num, cancel_year, cancel_reason, lib_link, app_num, app_year, present_date, present_time, present_venue, presentation_announcement) VALUES
('Web App for Course Management', 'A web platform for managing university courses and grades.', 'file_data_1', NULL, NULL, 3, NULL, NULL, 'Ypo Anathesi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('ML for Network Traffic', 'Using machine learning to analyze and secure network traffic.', 'file_data_2', NULL, 3, 3, NULL, NULL, 'Ypo Anathesi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('Blockchain Voting System', 'A secure and transparent voting system using blockchain technology.', 'file_data_3', NULL, 2, 1, 2, 4, 'Energi', '2024-09-23', NULL, NULL, NULL, NULL, 123,'2024', NULL, NULL, NULL, NULL),
('Stock Market Prediction', 'Predicting stock trends with time series and deep learning.', 'file_data_4', 'st_file_1', 1, 5, 1, 2, 'Ypo Eksetasi', '2024-10-03', NULL, NULL, NULL, NULL, 124, '2024', '2025-03-20', '11:00', 'online', ''),
('Smart City IoT Solutions', 'A study of IoT devices for improving city services.', 'file_data_5', 'st_file_5', 9, 4, 2, 1, 'Ypo Eksetasi', '2025-01-10', NULL, NULL, NULL, 'link_1', 125, '2024', '2025-08-01', '13:00', 'office_4', ''),
('Genetic Algorithms for Optimization', 'Applying genetic algorithms to solve complex optimization problems.', 'file_data_6', 'st_file_6', 10, 5, 3, 4, 'Peratomeni', '2023-05-21', NULL, NULL, NULL, 'Link_2', 102, '2023', '2023-12-10', '12:00', 'online', ''),
('Mobile Game Development', 'Designing and implementing a mobile game from scratch.', 'file_data_7', NULL, 7, 2, 3, 5, 'Energi', '2022-04-11', NULL, NULL, NULL, NULL, 100, '2022', NULL, NULL, NULL, NULL),
('AR for Cultural Heritage', 'An augmented reality app for exploring historical sites.', 'file_data_8', NULL, 2, 4, NULL, NULL,'Akyromeni' ,'2024-06-15', 50, '2024', 'Allagi thematos', NULL, 110, '2024', NULL, NULL, NULL, NULL);

INSERT INTO examiners VALUES
(2, 4, '2025-08-20', NULL, 'Energi'),
(2, 1, '2025-08-20', '2025-08-26', 'Apodexthike'),
(2, 5, '2025-08-05', '2025-08-18', 'Aporifthike'),
(3, 2, '2024-07-10', '2024-06-20', 'Apodexthike'),
(3, 4, '2024-07-10', '2024-09-23', 'Apodexthike'),
(3, 3, '2024-07-10', NULL, 'Akirwmeni'),
(4, 1, '2024-09-05', '2024-09-15', 'Apodexthike'),
(4, 2, '2024-09-05', '2024-10-03', 'Apodexthike'),
(5, 2, '2024-12-05', '2024-12-15', 'Apodexthike'),
(5, 1, '2024-12-05', '2024-12-15', 'Apodexthike'),
(6, 3, '2023-11-05', '2023-11-15', 'Apodexthike'),
(6, 4, '2023-11-05', '2023-12-10', 'Apodexthike'),
(7, 3, '2022-03-20', '2022-03-25', 'Apodexthike'),
(7, 5, '2022-03-20', '2022-04-11', 'Apodexthike');

INSERT INTO notes(diplwm_id, professor, notes) VALUES
(3, 1, 'note_1');

INSERT INTO grades VALUES
(5, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10),
(6, 09, 09, 09, 09, 09, 09, 09, 09, 09, 09, 09, 09, 09);


INSERT INTO secretary_action (diplwm_id, secret_id, prev_status, curr_status, cancel_reason,gs_date) VALUES
(6, 2, 'Ypo Eksetasi', 'Peratomeni', NULL, NULL),
(8, 1, 'Energi', 'Akyromeni', 'Allagh thematos', '2024-07-25');






