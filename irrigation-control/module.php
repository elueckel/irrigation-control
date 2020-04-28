<?

if (!defined('vtBoolean')) {
    define('vtBoolean', 0);
    define('vtInteger', 1);
    define('vtFloat', 2);
    define('vtString', 3);
    define('vtArray', 8);
    define('vtObject', 9);
}


	class Irrigation_Control extends IPSModule
	
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			//Properties
			$this->RegisterTimer('Execute', 0, 'IC_Execute($_IPS["TARGET"]);');
					
	
		}
	
	public function ApplyChanges()
	{
			
		//Never delete this line!
		parent::ApplyChanges();
										
		$this->SetResetTimerInterval();
			
	}
	
	public function SetResetTimerInterval() {
	  $now = new DateTime();
	  $target = new DateTime();
	  $target->modify('+1 day');
	  $target->setTime(12, 45, 0);
	  $diff = $target->getTimestamp() - $now->getTimestamp();
	  $interval = $diff * 1000;
	  $this->SetTimerInterval('Execute', $interval);
	} 	
	
		
	}
?>
