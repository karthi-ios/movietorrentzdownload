<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2017 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

if (!defined('BB_ROOT')) {
    die(basename(__FILE__));
}

require_once INC_DIR . '/functions_admin.php';

// Синхронизация
sync('topic', 'all');
sync('user_posts', 'all');
sync_all_forums();

// Чистка bb_poll_users
if ($poll_max_days = (int)config('tp.poll_max_days')) {
    $per_cycle = 20000;
    $row = OLD_DB()->fetch_row("SELECT MIN(topic_id) AS start_id, MAX(topic_id) AS finish_id FROM " . BB_POLL_USERS);
    $start_id = (int)$row['start_id'];
    $finish_id = (int)$row['finish_id'];

    while (true) {
        set_time_limit(600);
        $end_id = $start_id + $per_cycle - 1;

        OLD_DB()->query("
			DELETE FROM " . BB_POLL_USERS . "
			WHERE topic_id BETWEEN $start_id AND $end_id
				AND vote_dt < DATE_SUB(NOW(), INTERVAL $poll_max_days DAY)
		");

        if ($end_id > $finish_id) {
            break;
        }

        $start_id += $per_cycle;
    }
}

// Чистка user_newpasswd
OLD_DB()->query("UPDATE " . BB_USERS . " SET user_newpasswd = '' WHERE user_lastvisit < " . (TIMENOW - 7 * 86400));

// Чистка кеша постов
if ($posts_days = (int)config('tp.posts_cache_days_keep')) {
    OLD_DB()->query("DELETE FROM " . BB_POSTS_HTML . " WHERE post_html_time < DATE_SUB(NOW(), INTERVAL $posts_days DAY)");
}
