<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function p_ai_get_stats()
{
  $query = '
SELECT count(*)
  FROM `'.P_AI_TICKETS_TABLE.'`
  WHERE NOT status = \'pending\'
;';
  list($nb_of_completed_jobs) = pwg_db_fetch_row(pwg_query($query));

  $query = '
SELECT count(*)
  FROM `'.P_AI_TICKETS_TABLE.'`
  WHERE status = \'pending\'
;';
  list($nb_of_pending_jobs) = pwg_db_fetch_row(pwg_query($query));

  $query = '
SELECT count(*)
  FROM `'.P_AI_TICKETS_TABLE.'`
  WHERE status = \'failed\'
;';
  list($nb_of_failed_jobs) = pwg_db_fetch_row(pwg_query($query));
  
  $query = '
SELECT count(*)
  FROM `'.TAGS_TABLE.'`
  WHERE ai = \'true\'
;';
  list($nb_of_generated_tags) = pwg_db_fetch_row(pwg_query($query));

  $query = '
SELECT count(*)
  FROM `'.IMAGES_TABLE.'`
;';
  list($total_images) = pwg_db_fetch_row(pwg_query($query));

  $query = '
SELECT count(DISTINCT t.image_id)
  FROM `'.P_AI_TICKETS_TABLE.'` AS t
  INNER JOIN `'.IMAGES_TABLE.'` AS i ON i.id = t.image_id
  WHERE t.status = \'completed\'
;';
  list($analyzed_images) = pwg_db_fetch_row(pwg_query($query));

  return [
    'completed_jobs' => $nb_of_completed_jobs,
    'pending_jobs' => $nb_of_pending_jobs,
    'failed_jobs' => $nb_of_failed_jobs,
    'total_jobs' => $nb_of_completed_jobs + $nb_of_pending_jobs + $nb_of_failed_jobs,
    'tags' => $nb_of_generated_tags,
    'total_images' => $total_images,
    'analyzed_images' => $analyzed_images,
    'coverage_pct' => $total_images > 0 ? round($analyzed_images / $total_images * 100) : 0,
  ];
}

function p_ai_get_credits() {}