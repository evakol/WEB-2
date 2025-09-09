use university;

DELIMITER $
CREATE PROCEDURE kataxwrisi_vathmwn(
IN kat_diplwm_id INT,
IN kat_grade1_1 DECIMAL(4,2),
IN kat_grade1_2 DECIMAL(4,2),
IN kat_grade1_3 DECIMAL(4,2),
IN kat_grade1_4 DECIMAL(4,2),
IN kat_grade2_1 DECIMAL(4,2),
IN kat_grade2_2 DECIMAL(4,2),
IN kat_grade2_3 DECIMAL(4,2),
IN kat_grade2_4 DECIMAL(4,2),
IN kat_grade3_1 DECIMAL(4,2),
IN kat_grade3_2 DECIMAL(4,2),
IN kat_grade3_3 DECIMAL(4,2),
IN kat_grade3_4 DECIMAL(4,2)
)
BEGIN
DECLARE kat_final_grade DECIMAL(4,2);

SET kat_final_grade=
( 0.6*kat_grade1_1 + 0.15*kat_grade1_2 + 0.15*kat_grade1_3 + 0.1*kat_grade1_4 +
0.6*kat_grade2_1 + 0.15*kat_grade2_2 + 0.15*kat_grade2_3 + 0.1*kat_grade2_4 +
0.6*kat_grade3_1 + 0.15*kat_grade3_2 + 0.15*kat_grade3_3 + 0.1*kat_grade3_4) /3 ;

INSERT INTO grades (diplwm_id, final_grade,grade1_1, grade1_2, grade1_3, grade1_4,
grade2_1, grade2_2, grade2_3, grade2_4,grade3_1, grade3_2, grade3_3, grade3_4)
VALUES(kat_diplwm_id , kat_final_grade, kat_grade1_1, kat_grade1_2, kat_grade1_3, kat_grade1_4,
kat_grade2_1, kat_grade2_2, kat_grade2_3, kat_grade2_4, kat_grade3_1, kat_grade3_2, kat_grade3_3, kat_grade3_4);

END $
DELIMITER ;