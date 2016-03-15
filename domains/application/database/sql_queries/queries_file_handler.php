<?php

/**
 * SQL query templates for controller File_Handler
 * o controllers/controller_file_handler.php
 */
return array (
    0 => 'SELECT * FROM `files` WHERE f_file_path = :f_file_path',
    1 => 'INSERT INTO `files` (f_file_path, f_updated, f_raw_num) VALUES (:f_file_path, NOW(), :f_raw_num)',
    2 => 'INSERT INTO `domains` (d_f_id, d_domain, d_global_rank, d_visit_duration, d_pages_visit, d_bounce_rate, d_estimated_visits) VALUES (:d_f_id, :d_domain, :d_global_rank, :d_visit_duration, :d_pages_visit, :d_bounce_rate, :d_estimated_visits)',
    3 => 'DELETE FROM `files` WHERE f_id = :f_id',
    4 => 'DELETE FROM `domains` WHERE d_f_id = :d_f_id',
    5 => 'SELECT * FROM `domains` WHERE d_domain = :d_domain',
    6 => 'SELECT * FROM `files` ORDER BY `f_updated` DESC',
    7 => 'SELECT * FROM `files` WHERE f_id = :f_id',
    8 => 'DELETE FROM `files` WHERE f_id = :f_id',
    9 => 'DELETE FROM `domains` WHERE d_f_id = :d_f_id',
    10 => 'UPDATE `files` SET f_duplic_num = f_duplic_num + :f_duplic_num, '
    . 'f_error_num = f_error_num + :f_error_num, '
    . 'f_bounce_rate_out = f_bounce_rate_out + :f_bounce_rate_out, '
    . 'f_pages_visit_out = f_pages_visit_out + :f_pages_visit_out, '
    . 'f_visit_duration_out = f_visit_duration_out + :f_visit_duration_out, '
    . 'f_keywords_out = f_keywords_out + :f_keywords_out '
    . 'WHERE f_id = :f_id',
    11 => 'SELECT * FROM `domains` WHERE d_f_id = :d_f_id',
    12 => 'UPDATE `files` SET f_added_num = :f_added_num WHERE f_id = :f_id',
);
?>