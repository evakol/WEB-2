DELIMITER $
CREATE TRIGGER apodoxi_proskliseon
AFTER UPDATE ON examiners
FOR EACH ROW
BEGIN
DECLARE accept_exams INT;
DECLARE curr_diplwm_status ENUM (' Ypo Anathesi', ' Energi', 'Ypo Eksetasi', 'Peratomeni', 'Akyromeni');
DECLARE examiner_1 INT;
DECLARE examiner_2 INT;

IF NEW.status = 'Apodexthike' THEN
SELECT COUNT(*) INTO accept_exams
FROM examiners
WHERE diplwm_id = NEW.diplwm_id AND status = 'Apodexthike';

SELECT status INTO curr_diplwm_status
FROM diplwmatiki
WHERE id_diplwm = NEW.diplwm_id;

IF accept_exams = 2 AND curr_diplwm_status = 'Ypo Anathesi' THEN
UPDATE diplwmatiki
SET status = 'Energi'
Where id_diplwm = NEW.diplwm_id;

UPDATE examiners
SET status = 'Aporifthike'
WHERE diplwm_id = NEW.diplwm_id AND status = 'Energi';

END IF;

SELECT exam_1 INTO examiner_1
FROM diplwmatiki
WHERE id_diplwm = NEW.diplwm_id;

SELECT exam_2 INTO examiner_2
FROM diplwmatiki
WHERE id_diplwm = NEW.diplwm_id;

IF accept_exams <= 2 THEN
IF examiner_1 = NULL THEN
UPDATE diplwmatiki
SET exam_1 = NEW.exam_id
WHERE id_diplwm = NEW.diplwm_id;
ELSE IF examiner_2 = NULL THEN
UPDATE diplwmatiki
SET exam_2 = NEW.exam_id
WHERE id_diplwm = NEW.diplwm_id;
END IF;
END IF;

END IF;
END$

DELIMITER ;