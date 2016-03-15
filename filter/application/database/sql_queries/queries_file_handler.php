<?php

/**
 * SQL query templates for controller File_Handler
 * o controllers/controller_file_handler.php
 */
return array (
    0 => 'SELECT * FROM `files` WHERE f_file_path = :f_file_path',
    1 => 'INSERT INTO `files` (f_file_path, f_updated) VALUES (:f_file_path, NOW())',
    2 => 'INSERT INTO `emails` (e_f_id, e_email) VALUES (:e_f_id, :e_email)',
    3 => 'DELETE FROM `files` WHERE f_id = :f_id',
    4 => 'DELETE FROM `emails` WHERE e_f_id = :e_f_id',
    5 => 'SELECT * FROM `emails` WHERE e_email = :e_email',
    6 => 'SELECT * FROM `files` ORDER BY `f_updated` DESC',
    7 => 'SELECT * FROM `files` WHERE f_id = :f_id',
    8 => 'DELETE FROM `files` WHERE f_id = :f_id',
    9 => 'DELETE FROM `emails` WHERE e_f_id = :e_f_id',
    10 => 'UPDATE `files` SET '
    . 'f_raw_num = f_raw_num + :f_raw_num, '
    . 'f_added_num = f_added_num + :f_added_num, '
    . 'f_duplic_num = f_duplic_num + :f_duplic_num, '
    . 'f_error_num = f_error_num + :f_error_num '
    . 'WHERE f_id = :f_id',
);
?>