#!/usr/bin/php
<?php

require_once dirname(__FILE__) . '/../define_root.php';

// Set maximum execution time to 6 minutes as cron runs every 7 minutes. (There should be better way to do this)
$start_time = microtime(true);
$max_exec_time = 6 * 60;
set_time_limit($max_exec_time);

// Define vars
$start_date = date('r');
$lockfile = INCLUDE_ROOT . 'tmp/cron.lock';

/**  Define cron specific functions */
// log to formr log file
function cron_log($message, $cron_log = true) {
	$cron_logfile = INCLUDE_ROOT . 'tmp/logs/cron.log';
	$logfile = INCLUDE_ROOT . 'tmp/logs/errors.log';
	$message = date('Y-m-d H:i:s') . ' ' . $message . "\n";
	if ($cron_log) {
		return error_log($message, 3, $cron_logfile);
	}
	error_log($message, 3, $logfile);
}

// clean up on shutdown
function cron_cleanup($interrupt = null) {
	global $lockfile, $start_time, $max_exec_time;
	$exec_time = microtime(true) - $start_time;
	if ($exec_time > $max_exec_time) {
		$msg = "[$interrupt] Cron exceeded or reached set maximum script execution time of $max_exec_time secs.";
		cron_log($msg);
	}

	if (file_exists($lockfile)) {
		unlink($lockfile);
		cron_log("Cronfile cleanup complete");
	}
}

function cron_parse_executed_types($types) {
	$str = '';
	foreach ($types as $key => $value) {
		$str .= " {$value} {$key}s,";
	}
	return $str;
}

function cron_interrupt($signo) {
	switch ($signo) {
		// Set terminated flag to be able to terminate program securely
		// to prevent from terminating in the middle of the process
		// Use Ctrl+C to send interruption signal to a running program
		case SIGINT:
		case SIGTERM:
			cron_cleanup('SIGINT|SIGTERM');
		break;
		// @example: $ kill -s SIGUSR1 <pid>
		case SIGUSR1:
			cron_cleanup('SIGUSR1');
		break;
	}
}

// Register signal handlers that should be able to kill the cron in case some other weird shit happens apart from cron exiting cleanly
if (extension_loaded('pcntl')) {
	pcntl_signal(SIGINT, 'cron_interrupt');
	pcntl_signal(SIGTERM, 'cron_interrupt');
	pcntl_signal(SIGUSR1, 'cron_interrupt');
}

// even though the cronjobs are supposed to run only 6 min and are spaced 7 min, there seem to be problems due to overlapping CJs
// the lockfile is supposed to fix this
if (file_exists($lockfile)) {
	global $start_date;
	$started = file_get_contents($lockfile);
	cron_log("Cron overlapped. Started: $started, Overlapped: $start_date");

	// hack to delete $lockfile if cron hangs for more that 30 mins
	if ((strtotime($started) + (30 * 60)) < time()) {
		cron_log("Forced delete of $lockfile");
		unlink($lockfile);
	}
	exit(0);
}

// Lock cron
file_put_contents($lockfile, $start_date);
register_shutdown_function('cron_cleanup');

/** Do the Work */
cron_log("Cron started .... {$start_date}", true);

// Global required variables
$site = Site::getInstance();
$fdb = DB::getInstance();
$user = new User($fdb, null, null);
$user->cron = true;

// Wrap in a try catch just in case because we can't see shit
try {
	// Get all runs
	$runs = $fdb->select('name')->from('survey_runs')->where('cron_active = 1')->order('RAND')->fetchAll();

	$r = 0;
	foreach ($runs as $run_data):
		$i = 0;
		$r++;
		$done = array('Pause' => 0, 'Email' => 0, 'SkipForward' => 0, 'SkipBackward' => 0, 'Shuffle' => 0);
		$created = date('Y-m-d H:i:s');

		$run = new Run($fdb, $run_data['name']);
		if (!$run->valid) {
			alert("This run '{$run_data['name']}' caused problems", 'alert-danger');
			continue;
		}

		// get all session codes that have Branch, Pause, or Email lined up (not ended)
		$dues = $run->getCronDues();

		// Foreach session, execute all units
		foreach ($dues as $session) {
			$run_session = new RunSession($fdb, $run->id, 'cron', $session, $run);
			// Q. How will this go through all units of a session?
			$types = $run_session->getUnit(); // start looping thru their units.
			$i++;

			if ($types === false):
				alert("This session '$session' caused problems", 'alert-danger');
				continue 1;
			endif;

			foreach ($types as $type => $nr) {
				if (!isset($done[$type])) {
					$done[$type] = 0;
				}
				$done[$type] += $nr;
			}
		}

		// Build message report for current Run (saved in cron log)
		$alert_types = $site->alert_types;
		$alerts = $site->renderAlerts();
		$alerts = str_replace('<button type="button" class="close" data-dismiss="alert">&times;</button>', '', $alerts);

		$executed_types = '[none]';
		if (!empty($types)) {
			$executed_types = cron_parse_executed_types($types);
		}

		$msg = date('Y-m-d H:i:s') . ' ' . "$i sessions in the run " . $run->name . " were processed. {$executed_types} ended.<br />" . "\n";
		$msg .= $alerts;

		// Save cron log (This should be moved to logging in file system to avoid clustering DB)
		unset($done["Page"]);
		if (array_sum($done) > 0 OR array_sum($alert_types) > 0) {
			$log = $fdb->prepare("
			INSERT INTO `survey_cron_log` (run_id, created, ended, sessions, skipforwards, skipbackwards, pauses, emails, shuffles, errors, warnings, notices, message)
			VALUES (:run_id, :created, NOW(), :sessions, :skipforwards, :skipbackwards, :pauses, :emails, :shuffles, :errors, :warnings, :notices, :message)");
			$log->bindParam(':run_id', $run->id);
			$log->bindParam(':created', $created);
			$log->bindParam(':sessions', $i);
			$log->bindParam(':skipforwards', $done['SkipForward']);
			$log->bindParam(':skipbackwards', $done['SkipBackward']);
			$log->bindParam(':pauses', $done['Pause']);
			$log->bindParam(':emails', $done['Email']);
			$log->bindParam(':shuffles', $done['Shuffle']);
			$log->bindParam(':errors', $alert_types['alert-danger']);
			$log->bindParam(':warnings', $alert_types['alert-warning']);
			$log->bindParam(':notices', $alert_types['alert-info']);
			$log->bindParam(':message', $msg);
			$log->execute();
		}

		//echo $msg . "<br />";
		cron_log(strip_tags($msg), true);
		if (microtime(true) - $start_time > $max_exec_time) {
			throw new Exception("Cron Intercepted! Started at: $start_date, Intercepted at: " . date('r'));
		}
	endforeach;
} catch (Exception $e) {
	cron_log('Cron [Exception]: ' . $e->getMessage());
	cron_log('Cron [Exception]: ' . $e->getTraceAsString());
}

$user->cron = false;

$minutes = round((microtime(true) - $start_time) / 60, 3);
$end_date = date('r');
cron_log("Cron ended .... {$end_date}. Took ~{$minutes} minutes", true);
// Do cleanup just in case
cron_cleanup();
