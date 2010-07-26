<?php


class bracket extends db {
	
	#@todo: set-methods: ugly, too many. any better way?

	protected $id;
	protected $name;
	protected $tournament_id;
	protected $status;
	protected $start_time;
	protected $end_time;
	protected $mode;
	protected $type;
	protected $timelimit1;
	protected $timelimit2;
	protected $timelimit3;
	protected $timelimit4;
	protected $timelimit5;
	protected $pause1;
	protected $pause2;
	protected $pause3;
	protected $pause4;
	protected $pause5;
	


	

	public function __construct() {

		parent::__construct ('brackets');
		
	}
	
	
	
	public function setup($name, $type){
		db::store('name, type, tournament_id, status', "'$name', '$type', '".$_SESSION['tournament_id']."', '1'");
	}
		
	
	
	
	public function get_id(){
		return $this->id;
	}
	
	public function get_name(){
		return $this->name;
	}
	
	public function get_type(){
		return $this->type;
	}
	
	public function get_tournament_id(){
		return $this->tournament_id;
	}
	
	public function get_status(){
		return $this->status;
	}
	
	public function get_start_time(){
		return $this->start_time;
	}
	
	public function get_end_time(){
		return $this->end_time;
	}
	
	public function get_mode(){
		return $this->mode;
	}
	
	public function get_timelimit1(){
		return $this->timelimit1;
	}
	
	public function get_pause1(){
		return $this->pause1;
	}
	
	public function get_courts(){
		$occupation = new court_occupation();
		return $occupation->get_courts_for_bracket($this->id);
	}
	
	public function set_type($type){
		self::update("type='$type'", "id='$this->id'");
	}
	
	public function set_timelimit1($value){
		self::update("timelimit1='$value'", "id='$this->id'");
	}
	
	public function set_pause1($value){
		self::update("pause1='$value'", "id='$this->id'");
	}
	
	public function set_timelimit2($value){
		self::update("timelimit2='$value'", "id='$this->id'");
	}
	
	public function set_pause2($value){
		self::update("pause2='$value'", "id='$this->id'");
	}
	
	public function set_timelimit3($value){
		self::update("timelimit3='$value'", "id='$this->id'");
	}
	
	public function set_pause3($value){
		self::update("pause3='$value'", "id='$this->id'");
	}
	
	public function set_timelimit4($value){
		self::update("timelimit4='$value'", "id='$this->id'");
	}
	
	public function set_pause4($value){
		self::update("pause4='$value'", "id='$this->id'");
	}
	
	public function set_timelimit5($value){
		self::update("timelimit5='$value'", "id='$this->id'");
	}
	
	public function set_pause5($value){
		self::update("pause5='$value'", "id='$this->id'");
	}
	
	public function set_status($value){
		self::update("status='$value'", "id='$this->id'");
	}
	
	public function calculate_time_needed($timelimit, $pause){
		
		#@todo: not sure if result is right
		return self::get_number_of_matches()*$pause*$timelimit;
		
	}
	
	public function get_number_of_matches(){
		return sizeof(parent::fetch_results("SELECT id FROM matches WHERE bracket_id = '$this->id'"));
	}
	
	public function get_number_of_teams(){
		return sizeof(self::get_qualified_teams());
	}

	
	public function get_qualified_teams(){
		$qualified = new bracket_qualification($this->id);
		return $qualified->get_qualified_teams();
	}

	
//	protected function store(){
//				
//		parent::store('
//				tournament_id, bracket_name, type, mode, status,
//				timelimit1, pause1,
//				timelimit2, pause2,
//				timelimit3, pause3,
//				timelimit4, pause4,
//				timelimit5, pause5
//			', "
//				'".$_SESSION['tournament_id']."', '$this->bracket_name', '$this->type', '$this->mode', '0',
//				'$this->timelimit5', '$this->pause5',
//				'$this->timelimit4', '$this->pause4',
//				'$this->timelimit3', '$this->pause3',
//				'$this->timelimit2', '$this->pause2',
//				'$this->timelimit1', '$this->pause1'
//
//		");
//
//	}

	
	

	
	
	public function delete($id){
		unset ($_SESSION['bracket_id'], $_SESSION['temp']['bracket']);
		
		
		parent::query("
			DELETE FROM
				upc_matches
			WHERE
				match_id IN (SELECT id FROM matches WHERE bracket_id='$id')
		");
		
		parent::query("
			DELETE FROM
				goals
			WHERE
				match_id IN (SELECT id FROM matches WHERE bracket_id='$id')
		");
		
		parent::query("
			DELETE FROM
				fouls
			WHERE
				match_id IN (SELECT id FROM matches WHERE bracket_id='$id')
		");
		
		parent::query("
			DELETE FROM
				matches
			WHERE
				bracket_id='$id'
		");
		
		parent::query("
			DELETE FROM
				bracket_qualifications
			WHERE
				bracket_id='$id'
		");
		parent::query("
			DELETE FROM
				seeding
			WHERE
				bracket_id='$id'
		");
		
		parent::delete($id);
		
	}

	
	public function seeding(){
		$qualified = new bracket_qualification($this->id);
		$teams = $qualified->get_qualified_teams();
		$db = new db('seeding');
		
		foreach ($teams as $team){
			$db->query("INSERT INTO seeding (team_id, bracket_id) VALUES ('".$team."', '$this->id')");
		}
	}
	
	public function get_seeding(){
		$db = new db('seeding');
		return $db->fetch_results("SELECT @rownum:=@rownum+1 nr, s.id, s.team_id, t.name FROM (SELECT @rownum:=0) r, seeding s INNER JOIN teams as t on t.id=s.team_id WHERE s.bracket_id='$this->id' ORDER BY s.value");

	}
	
	
	protected function store_matchlist($matchlist){
		
		#@todo: set bracket status to sth so the schedule can't be stored more than once due to just reloading the form
		
		$check = new bracket();
		$match_order = 0;
		
		$courts = self::get_courts();
		
		
			foreach($matchlist as $match){
			
			$team1 = $match[0];
			$team2 = $match[1];
			$identifier = $match[2];
			
			$new_match = new match();
			$new_match->store("'$this->id', '$team1', '$team2', '$identifier'");
			
			$match_id = $new_match->insert_id;
			
			$nextcourt = each($courts);
			if ($nextcourt == FALSE){
				reset($courts);
				$nextcourt = each ($courts);
			}
			$nextcourt = $nextcourt['value'];
			$court_id = $nextcourt['id'];
			
			$upc_match = new upc_match();
			$upc_match->store($match_id, $court_id, $match_order);

			$match_order++;
			}	

	}
	
	
	public function set_qualified_teams($mode, $top_nr = '', $from_bracket = ''){
		
		switch ($mode){
			
			case 'top':
				$bracket = new bracket();
				$bracket->load_entry($from_bracket);
				$type = $bracket->get_type();
				$bracket = new $type();
				$bracket->load_entry($from_bracket);
				$ranking = $bracket->get_ranking('',TRUE);
				$qualified = array_slice($ranking, 0, $top_nr);
			break;
			
			case 'all':
				$registration = new registration();
				$qualified = $registration->get_registered_teams($this->tournament_id);
			break;
			
		}
		
		$bracket_qualification = new bracket_qualification($this->id);
		foreach ($qualified as $team){
			$bracket_qualification->add($team['id']);
		}
	}
	
	public function process_finished_match(){
		return TRUE;
	}
}

?>