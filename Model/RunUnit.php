<?php

class RunUnitFactory {

	protected $supported = array('Survey', 'Pause', 'Email', 'External', 'Page', 'SkipBackward', 'SkipForward', 'Shuffle');

	public function make($dbh, $session, $unit, $run_session = NULL) {
		if (empty($unit['type'])) {
			$unit['type'] = 'Survey';
		}
		$type = $unit['type'];

		if (!in_array($type, $this->supported)) {
			throw new Exception("Unsupported unit type '$type'");
		}

		return new $type($dbh, $session, $unit, $run_session);
	}

	public function getSupportedUnits() {
		return $this->supported;
	}

}

class RunUnit {

	public $errors = array();
	public $id = null;
	public $user_id = null;
	public $run_unit_id = null; // this is the ID of the unit-to-run-link entry
	public $session = null;
	public $unit = null;
	public $ended = false;
	public $position;
	public $called_by_cron = false;
	public $knitr = false;
	public $session_id = null;
	public $run_session_id = null;
	public $type = '';
	public $icon = 'fa-wrench';
	public $special = false;
	public $valid;
	protected $non_user_tables = array('survey_users', 'survey_run_sessions', 'survey_unit_sessions', 'survey_items_display', 'survey_email_log', 'shuffle');
	protected $non_session_tables = array('survey_users', 'survey_run_sessions', 'survey_unit_sessions');

	/**
	 * @var DB
	 */
	protected $dbh;

	public function __construct($fdb, $session = null, $unit = null, $run_session = null) {
		$this->dbh = $fdb;
		$this->session = $session;
		$this->unit = $unit;
		$this->run_session = $run_session;

		if (isset($unit['run_id'])) {
			$this->run_id = $unit['run_id'];
		}

		if (isset($unit['run_unit_id'])) {
			$this->run_unit_id = $unit['run_unit_id'];
		} elseif (isset($unit['id'])) {
			$this->run_unit_id = $unit['id'];
		}

		if (isset($unit['run_name'])) {
			$this->run_name = $unit['run_name'];
		}

		if (isset($unit['session_id'])) {
			$this->session_id = $unit['session_id'];
		}

		if (isset($unit['run_session_id'])) {
			$this->run_session_id = $unit['run_session_id'];
		}

		if (isset($this->unit['unit_id'])) {
			$this->id = $this->unit['unit_id'];
		}

		if (isset($this->unit['position'])) {
			$this->position = (int) $this->unit['position'];
		}

		if (isset($this->unit['special'])) {
			$this->special = $this->unit['special'];
		}

		if (isset($this->unit['cron'])) {
			$this->called_by_cron = true;
		}
	}

	public function create($type) {
		$this->dbh->exec("INSERT INTO `survey_units` SET type = :type, created = NOW(), modified = NOW()", array('type' => $type));
		$this->unit_id = $this->dbh->lastInsertId();
		return $this->unit_id;
	}

	public function modify($id) {
		return $this->dbh->exec("UPDATE `survey_units` SET modified = NOW() WHERE id = :id", array('id' => $id));
	}

	protected function beingTestedByOwner() {
		if ($this->run_session === null OR $this->run_session->user_id == $this->run_session->run_owner_id) {
			return true;
		}
		return false;
	}

	public function linkToRun() {
		return $this->dbh->update('survey_run_units', 
				array('unit_id' => $this->id), 
				array('id' => $this->run_unit_id), 
				array('int'), array('int')
		);
	}

	public function addToRun($run_id, $position = 1) {
		if (!is_numeric($position)) {
			$position = 1;
		}
		$this->position = (int) $position;
		$this->run_unit_id = $this->dbh->insert('survey_run_units', array(
			'unit_id' => $this->id,
			'run_id' => $run_id,
			'position' => $position,
		));
		return $this->run_unit_id;
	}

	public function removeFromRun() {
		return $this->dbh->delete('survey_run_units', array('id' => $this->run_unit_id));
	}

	public function delete() {
		$affected = $this->dbh->delete('survey_units', array('id' => $this->id));
		if ($affected): // remove from all runs
			$affected += $this->dbh->delete('survey_run_units', array('unit_id' => $this->id));
		endif;

		return $affected;
	}

	public function end() { // todo: logically this should be part of the Unit Session Model, but I messed up my logic somehow
		$updated = $this->dbh->exec(
			"UPDATE `survey_unit_sessions` SET `ended` = NOW() WHERE `id` = :session_id AND `unit_id` = :unit_id AND `ended` IS NULL LIMIT 1", 
			array('session_id' => $this->session_id, 'unit_id' => $this->id)
		);

		if ($updated === 1):
			$this->ended = true;
			return true;
		else:
			return false;
		endif;
	}

	protected function getSampleSessions() {
		$results = $this->dbh->select('session, id, position')
						->from('survey_run_sessions')
						->where(array('run_id' => $this->run_id))
						->limit(20)->fetchAll();
		if (!$results) {
			echo 'No data to compare to yet.'; // FIXME echo to where?
			return false;
		}
		return false;
	}

	protected function howManyReachedItNumbers() {
		$reached = $this->dbh->select(array('SUM(`survey_unit_sessions`.ended IS NULL)' => 'begun', 'SUM(`survey_unit_sessions`.ended IS NOT NULL)' => 'finished'))
						->from('survey_unit_sessions')
						->leftJoin('survey_run_sessions', 'survey_run_sessions.id = survey_unit_sessions.run_session_id')
						->where('survey_unit_sessions.unit_id = :unit_id')
						->where('survey_run_sessions.run_id = :run_id')
						->bindParams(array('unit_id' => $this->id, 'run_id' => $this->run_id))
						->fetch();

		return $reached;
	}

	protected function howManyReachedIt() {
		$reached = $this->howManyReachedItNumbers();
		if ($reached['begun'] === "0") {
			$reached['begun'] = "";
		}
		if ($reached['finished'] === "0") {
			$reached['finished'] = "";
		}
		return "<span class='hastooltip badge' title='Number of unfinished sessions'>" . $reached['begun'] . "</span> <span class='hastooltip badge badge-success' title='Number of finished sessions'>" . $reached['finished'] . "</span>";
	}

	public function runDialog($dialog) {

		if (isset($this->position)) {
			$position = $this->position;
		} elseif (isset($this->unit) && isset($this->unit['position'])) {
			$position = $this->unit['position'];
		} else {
			$position = $this->dbh->findValue('survey_run_units', array('id' => $this->run_unit_id), array('position'));
		}

		return '
		<div class="col-xs-12 row run_unit_inner ' . $this->type . '" data-type="' . $this->type . '">
				<div class="col-xs-3 run_unit_position">
					<h1><i class="muted fa fa-2x ' . $this->icon . '"></i></h1>
					' . $this->howManyReachedIt() . ' <button href="ajax_remove_run_unit_from_run" class="remove_unit_from_run btn btn-xs hastooltip" title="Remove unit from run" type="button"><i class="fa fa-times"></i></button>
<br>
					<input class="position" value="' . $position . '" type="number" name="position[' . $this->run_unit_id . ']" step="1" max="32000" min="-32000"><br>
				</div>
			<div class="col-xs-9 run_unit_dialog">
				<input type="hidden" value="' . $this->run_unit_id . '" name="run_unit_id">
				<input type="hidden" value="' . $this->id . '" name="unit_id">
				<input type="hidden" value="' . $this->special . '" name="special">' . $dialog . '
			</div>
		</div>';
	}

	public function displayForRun($prepend = '') {
		return parent::runDialog($prepend, '<i class="fa fa-puzzle-piece"></i>'); // FIXME: This class has no parent
	}

	protected $survey_results;

	public function getUserDataInRun($needed) {
		$surveys = $needed['matches'];
		$results_tables = $needed['matches_results_tables'];
		$matches_variable_names = $needed['matches_variable_names'];
		$this->survey_results = array('datasets' => array());
		
		foreach($surveys AS $study_id => $survey_name) :
			if(!isset($this->survey_results['datasets'][$survey_name])):
				
				$results_table = $results_tables[$survey_name];
				if(empty($matches_variable_names[ $survey_name ])) {
					$variables = "NULL AS formr_dummy";
				} else {
					$variables = "`$results_table`.`" . implode("`,`$results_table`.`" ,$matches_variable_names[ $survey_name ]) . '`';
				}
				
				$q1 = "SELECT $variables";
				if($this->run_session_id === NULL AND !in_array($survey_name, $this->non_session_tables)) { // todo: what to do with session_id tables in faketestrun
					$q3 = " WHERE `$results_table`.session_id = :session_id;"; // just for testing surveys
				} else {
					$q3  = " WHERE  `survey_run_sessions`.id = :run_session_id;";
				}

				if(!in_array($survey_name, $this->non_session_tables )) {
					$q2 = "
						LEFT JOIN `survey_unit_sessions` ON `$results_table`.session_id = `survey_unit_sessions`.id
						LEFT JOIN `survey_run_sessions` ON `survey_run_sessions`.id = `survey_unit_sessions`.run_session_id
					";
				} elseif($survey_name == 'survey_unit_sessions'){
					$q2 = "LEFY JOIN `survey_run_sessions` ON `survey_run_sessions`.id = `survey_unit_sessions`.run_session_id";
				} elseif($survey_name == 'survey_run_sessions') {
					$q2 = "";
				} elseif($survey_name == 'survey_users') {
					$q2 = "LEFT JOIN `survey_run_sessions` ON `survey_users`.id = `survey_run_sessions`.user_id";
				}

				$q1 .= " FROM `$results_table` ";

				$q = $q1 . $q2 . $q3;
				
				
				$get_results = $this->dbh->prepare($q);
				if($this->run_session_id === NULL) {
					$get_results->bindValue(':session_id', $this->session_id);
				} else {
					$get_results->bindValue(':run_session_id', $this->run_session_id);
				}
				$get_results->execute();

				$this->survey_results['datasets'][$survey_name] = array();
				while($res = $get_results->fetch(PDO::FETCH_ASSOC)):
					foreach($res AS $var => $val):
						if(!isset($this->survey_results['datasets'][$survey_name][$var])) {
							$this->survey_results['datasets'][$survey_name][$var] = array();
						}
						$this->survey_results['datasets'][$survey_name][$var][] = $val;
					endforeach;
				endwhile;
			endif;
		endforeach;

		if(!empty($needed['variables'])):
			if(in_array('formr_last_action_date',$needed['variables']) OR in_array('formr_last_action_time',$needed['variables'])):
				$last_action = $this->dbh->execute(
					"SELECT `created` FROM `survey_unit_sessions` WHERE `id` = :session_id AND `unit_id` = :unit_id AND `ended` IS NULL LIMIT 1",
					array('session_id' => $this->session_id, 'unit_id' => $this->id),
					true
				);
				$last_action_time = strtotime($last_action);
				if(in_array('formr_last_action_date', $needed['variables'])):
					$this->survey_results['.formr$last_action_date'] = "as.Date('".date("Y-m-d", $last_action_time)."')";
				endif;
				if(in_array('formr_last_action_time',$needed['variables']) ):
					$this->survey_results['.formr$last_action_time'] = "as.POSIXct('".date("Y-m-d H:i:s T", $last_action_time)."')";
				endif;
			endif;
			
			if(in_array('formr_login_link',$needed['variables']) ):
				$this->survey_results['.formr$login_link'] = WEBROOT."{$this->run_name}?code={$this->session}";
			endif;
			if(in_array('formr_login_code',$needed['variables']) ):
				$this->survey_results['.formr$login_code'] = $this->session;
			endif;
		endif;

		if ($needed['token_add'] !== null AND ! isset($this->survey_results['datasets'][$needed['token_add']])):
			$this->survey_results['datasets'][$needed['token_add']] = array();
		endif;
		return $this->survey_results;
	}

	public function makeOpenCPU() {
		$openCPU = new OpenCPU(Config::get('opencpu_instance'));
		$openCPU->clearUserData();
		return $openCPU;
	}

	protected function knittingNeeded($source) {
		if (mb_strpos($source, '`r ') !== false OR mb_strpos($source, '```{r') !== false) {
			return true;
		}
		return false;
	}

	public function dataNeeded($fdb, $q, $token_add = NULL) {
		$matches_variable_names = $variable_names_in_table = $matches = $matches_results_tables = $results_tables = $tables = array();
		$results = $fdb->select(array('COALESCE(`survey_studies`.`results_table`,`survey_studies`.`name`)' => 'results_table', 'survey_studies.name', 'survey_studies.id'))
				->from('survey_studies')
				->leftJoin('survey_runs', 'survey_runs.user_id = survey_studies.user_id')
				->where('survey_runs.id = :run_id')
				->bindParams(array('run_id' => $this->run_id))
				->fetchAll();

		$tables = $this->non_user_tables;
		$results_tables = array_combine($this->non_user_tables, $this->non_user_tables);
		foreach ($results as $res) {
			$tables[$res['id']] = $res['name'];
			$results_tables[$res['name']] = $res['results_table'];
		}

		if($token_add !== null AND !in_array($this->name, $tables)):	 // send along this table if necessary
			$tables[$this->id] = $this->name;
			$results_tables[ $this->name ] = $this->results_table;
		endif;
	
		foreach($tables AS $study_id => $table_name):
		
			if($table_name == $token_add OR preg_match("/\b$table_name\b/",$q)): // study name appears as word, matches nrow(survey), survey$item, survey[row,], but not survey_2
				$matches[ $study_id ] = $table_name;
				$matches_results_tables[ $table_name ] = $results_tables[ $table_name ];
			endif;
		endforeach;

		foreach($matches AS $study_id => $table_name):

			if(in_array($table_name, $this->non_user_tables)):
				if($table_name == 'survey_users'):
					$variable_names_in_table[ $table_name ] = array("created","modified", "user_code","email","email_verified","mobile_number", "mobile_verified");
				elseif($table_name == 'survey_run_sessions'):
					$variable_names_in_table[ $table_name ] = array("session","created","last_access","position","current_unit_id", "deactivated","no_email");
				elseif($table_name == 'survey_unit_sessions'):
					$variable_names_in_table[ $table_name ] = array("created","ended","unit_id");
				elseif($table_name == 'survey_items_display'):
					$variable_names_in_table[ $table_name ] = array("created","answered_time","answered","displaycount","item_id");
				elseif($table_name == 'survey_email_log'):
					$variable_names_in_table[ $table_name ] = array("email_id","created","recipient");
				elseif($table_name == 'shuffle'):
					$variable_names_in_table[ $table_name ] = array("unit_id","created","group");
				endif;
			else:
				$items = $fdb->select('name')->from('survey_items')
					->where(array('study_id' => $study_id))
					->where("type NOT IN ('mc_heading', 'note', 'submit')")
					->fetchAll();

				$variable_names_in_table[ $table_name ] = array("created", "modified", "ended"); // should avoid modified, sucks for caching
				foreach ($items as $res) {
					$variable_names_in_table[ $table_name ][] = $res['name'];
				}
			endif;

			$matches_variable_names[ $table_name ] = array();
			foreach($variable_names_in_table[ $table_name ] AS $variable_name) {
				$variable_name_base = preg_replace("/_?[0-9]{1,3}R?$/","", $variable_name);  // try to match scales too
				if(strlen($variable_name_base) < 3) {
					$variable_name_base = $variable_name;
				}
				if(preg_match("/\b$variable_name\b/",$q) OR preg_match("/\b$variable_name_base\b/",$q)) { // item name appears as word, matches survey$item, survey[, "item"], but not item_2 for item-scale unfortunately
					$matches_variable_names[ $table_name ][] = $variable_name;
				}
			}

//			if(empty($matches_variable_names[ $table_name ])):
//				unset($matches_variable_names[ $table_name ]);
//				unset($variable_names_in_table[ $table_name ]);
//				unset($matches[ $study_id ]);
//			endif;
		endforeach;
	
		$variables = array();
		if(preg_match("/\btime_passed\b/",$q)) { $variables[] = 'formr_last_action_time'; }
		if(preg_match("/\bnext_day\b/",$q)) { $variables[] = 'formr_last_action_date'; }
		if(preg_match('/\b.formr\$login_code\b/',$q)) { $variables[] = 'formr_login_code'; }
		if(preg_match('/\b.formr\$login_link\b/',$q)) { $variables[] = 'formr_login_link'; }

		return compact("matches","matches_results_tables", "matches_variable_names", "token_add", "variables");
//		return $matches;
	}

	public function parseBodySpecial() {
		$openCPU = $this->makeOpenCPU();
		return $openCPU->knitForAdminDebug($this->body);
	}

	public function getParsedText($source) {
		$openCPU = $this->makeOpenCPU();
		if ($this->beingTestedByOwner()) {
			$openCPU->admin_usage = true;
		}

		$openCPU->addUserData($this->getUserDataInRun(
						$this->dataNeeded($this->dbh, $source)
		));

		return $openCPU->knit($source);
	}

	public function getParsedTextAdmin($source) {
		if (!$this->grabRandomSession()) {
			return false;
		}
		return $this->getParsedText($source);
	}

	private function grabRandomSession() {
		if ($this->run_session_id === NULL) {
			$current_position = -9999999;
			if (isset($this->unit['position'])) {
				$current_position = $this->unit['position'];
			}

			$temp_user = $this->dbh->select('session, id, position')
				->from('survey_run_sessions')
				->where(array('run_id' => $this->run_id, 'position >=' => $current_position))
				->limit(1)
				->fetch();

			if (!$temp_user) {
				echo 'No data to compare yet'; // FIXME echo to where?
				return false;
			}

			$this->run_session_id = $temp_user['id'];
		}

		return $this->run_session_id;
	}

	public function getParsedBodyAdmin($source, $email_embed = false) {
		if ($this->knittingNeeded($source)):
			if (!$this->grabRandomSession()) {
				return false;
			}

			$openCPU = $this->makeOpenCPU();
			if ($this->beingTestedByOwner()) {
				$openCPU->admin_usage = true;
			}

			$openCPU->addUserData($this->getUserDataInRun(
							$this->dataNeeded($this->dbh, $source)
			));

			if ($email_embed):
				return $openCPU->knitEmailForAdminDebug($source); # currently not caching email reports
			else:
				$report = $openCPU->knitForAdminDebug($source);
			endif;

			return $report;

		else:
			if ($email_embed):
				return array('body' => $this->body_parsed, 'images' => array());
			else:
				return $this->body_parsed;
			endif;
		endif;
	}

	public function getParsedBody($source, $email_embed = false) {
		if (!$this->knittingNeeded($source)) { // knit if need be
			if ($email_embed):
				return array('body' => $this->body_parsed, 'images' => array());
			else:
				return $this->body_parsed;
			endif;
		} else {
			if (!$email_embed) {
				$body_knit = $this->dbh->findValue('survey_reports', array('unit_id' => $this->id, 'session_id' => $this->session_id), array('body_knit'));
				if ($body_knit) {
					return $body_knit;
				}
			}

			$openCPU = $this->makeOpenCPU();
			if ($this->beingTestedByOwner()) {
				$openCPU->admin_usage = true;
			}

			$openCPU->addUserData($this->getUserDataInRun($this->dataNeeded($this->dbh, $source)));

			if ($email_embed):
				return $openCPU->knitEmail($source); # currently not caching email reports
			else:
				$report = $openCPU->knitForUserDisplay($source);
			endif;

			if ($openCPU->anyErrors()) {
				return false;
			}

			if ($report):
				try {
					$this->dbh->exec("
						INSERT INTO `survey_reports` (`session_id`, `unit_id`, `body_knit`, `created`, `last_viewed`) VALUES (:session_id, :unit_id, :body_knit,  NOW(), NOW())", 
						array('session_id' => $this->session_id, 'unit_id' => $this->id, 'body_knit' => $report)
					);
				} catch (Exception $e) {
					log_exception($e, __CLASS__);
					trigger_error("Couldn't save Knitr report, probably too large: " . human_filesize(strlen($report)), E_USER_WARNING);
				}
				return $report;
			endif;
		}
	}

	// when body is changed, delete all survey reports?
}
