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
		$this->RegisterPropertyBoolean("ComponentActive", 0);
		$this->RegisterPropertyString("Hour","11");
		$this->RegisterPropertyString("Minute","00");
		
		//Properties
		$this->RegisterTimer('Execute', 0, 'IC_Execute($_IPS["TARGET"]);');
				

	}

	public function ApplyChanges()
	{
			
		//Never delete this line!
		parent::ApplyChanges();
										
		//$this->SetResetTimerInterval();
					
	}
	
	public function StartIrrigation() {
		
		$this->SetResetTimerInterval();	
		
	}
		
		
	public function SetResetTimerInterval() {
		$Hour = $this->ReadPropertyString("Hour");
		$Minute = $this->ReadPropertyString("Minute");
		
		$now = new DateTime();
		$target = new DateTime();
		$target->modify('+1 day');
		$target->setTime($Hour, $Minute, 0);
		$diff = $target->getTimestamp() - $now->getTimestamp();
		$interval = $diff * 1000;
		$this->SetTimerInterval('Execute', $interval);
		$this->StartIrrigation();
	} 
		
	/*
	  public function SetResetTimerInterval() {
	  $now = new DateTime();
	  $target = new DateTime();
	  $target->modify('+1 day');
	  $target->setTime(12, 45, 0);
	  $diff = $target->getTimestamp() - $now->getTimestamp();
	  $interval = $diff * 1000;
	  $this->SetTimerInterval('Execute', $interval);
	} 	
	
	*/	

	
}

?>
