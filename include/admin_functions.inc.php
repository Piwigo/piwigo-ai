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
  FROM `'.TAGS_TABLE.'`
  WHERE ai = \'true\'
;';
  list($nb_of_generated_tags) = pwg_db_fetch_row(pwg_query($query));

  return [
    'completed_jobs' => $nb_of_completed_jobs,
    'pending_jobs' => $nb_of_pending_jobs,
    'tags' => $nb_of_generated_tags,
  ];
}

function p_ai_get_credits() {}