<?php

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

		//Sensors to figure out the environment
		
		
		$this->RegisterPropertyInteger("SensorRain",0);
		$this->RegisterPropertyBoolean("RainStopsIrrigation", 0);
		$this->RegisterPropertyInteger("SensorRainAmount",0);
		$this->RegisterPropertyInteger("SensorTemperature",0);
		$this->RegisterPropertyInteger("SensorWind",0);
		$this->RegisterPropertyInteger("InformationRainInXDays",0);
		
		//Configuration
		$this->RegisterPropertyInteger("MethodToEstimateDryout", 1); //Soil humidity = default ... maybe in the future Evotranspiration


		//Definitions
		//Humidity
		$this->RegisterPropertyBoolean("HumiditySensorActive", 0);
		$this->RegisterPropertyInteger("SensorSoilHumidity", 0);
		$this->RegisterPropertyInteger("EstimateDryoutDryingThreshold", 20);
		$this->RegisterPropertyInteger("EstimateDryoutDryThreshold", 50);

		$this->RegisterPropertyInteger("RainInXDaysMinimumDryingOutThreshold", 15);
		$this->RegisterPropertyInteger("RainInXDaysMinimumDryThreshold", 40);

		//Gruppe 1
		$this->RegisterPropertyBoolean("Group1Active", 0);
		$this->RegisterPropertyInteger("Group1NumberStartHour","22");
		$this->RegisterPropertyInteger("Group1NumberStartMinute","00");
		$this->RegisterPropertyBoolean("Group1ExecuteNowOrNext", 0);
		$this->RegisterPropertyInteger("Group1ExecutionInterval",0);
		$this->RegisterPropertyInteger("Group1OperationStartHour","22");
		$this->RegisterPropertyInteger("Group1OperationStartMinute","00");
		$this->RegisterPropertyInteger("Group1OperationEndHour","06");
		$this->RegisterPropertyInteger("Group1OperationEndMinute","00");
		

		//Group 1
		$this->RegisterPropertyBoolean("Group1String1Active", 0);
		$this->RegisterPropertyInteger("Group1String1Valve1", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String1Valve2", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String1Time", 0); //in Minutes
		$this->RegisterPropertyInteger("Group1String1LiterPerHour", 0); //l

		$this->RegisterPropertyBoolean("Group1String2Active", 0);
		$this->RegisterPropertyInteger("Group1String2Valve1", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String2Valve2", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String2Time", 0); //in Minutes
		$this->RegisterPropertyInteger("Group1String2LiterPerHour", 0); //l

		$this->RegisterPropertyBoolean("Group1String3Active", 0);
		$this->RegisterPropertyInteger("Group1String3Valve1", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String3Valve2", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String3Time", 0); //in Minutes
		$this->RegisterPropertyInteger("Group1String3LiterPerHour", 0); //l

		$this->RegisterPropertyBoolean("Group1String4Active", 0);
		$this->RegisterPropertyInteger("Group1String4Valve1", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String4Valve2", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String4Time", 0); //in Minutes
		$this->RegisterPropertyInteger("Group1String4LiterPerHour", 0); //l


		//timer stuff
		$this->RegisterPropertyBoolean("ComponentActive", 0);
		$this->RegisterPropertyInteger("Hour","03");
		$this->RegisterPropertyInteger("Minute","00");
		
		//Properties
		$this->RegisterTimer('SprinklerOperationGroup1', 0, 'IC_SprinklerOperationGroup1($_IPS["TARGET"]);'); //Test
		$this->RegisterTimer('Watchdog', 0, 'IC_Watchdog($_IPS["TARGET"]);'); //Timer to monitor things and perform frequent tasks
		$this->RegisterTimer('Group1SprinklerStringStop', 0, 'IC_Group1SprinklerStringStop($_IPS["TARGET"]);'); //Timer starting Irrigation

		//Variables always needed

		// https://www.irrometer.com/basics.html
		if (IPS_VariableProfileExists("IC.SoilHumidity") == false) {
			IPS_CreateVariableProfile("IC.SoilHumidity", 1);
			IPS_SetVariableProfileIcon("IC.SoilHumidity", "Shutter");
			IPS_SetVariableProfileAssociation("IC.SoilHumidity", 0, $this->Translate("Wet"), "", 0xffffff);
			IPS_SetVariableProfileAssociation("IC.SoilHumidity", 1, $this->Translate("Drying Out"), "", 0xffffff);
			IPS_SetVariableProfileAssociation("IC.SoilHumidity", 2, $this->Translate("Dry"), "", 0xffffff);
		}
		
		if (IPS_VariableProfileExists("IC.Timer") == false) {
			IPS_CreateVariableProfile("IC.Timer", 1);
			IPS_SetVariableProfileIcon("IC.Timer", "Clock");
			IPS_SetVariableProfileDigits("IC.Timer", 0);
			IPS_SetVariableProfileValues("IC.Timer", 0, 60, 0);
		}

		$this->RegisterVariableBoolean('ManualActivationSprinkler', $this->Translate('WF Manual Sprinkler Activation'),"~Switch");		
		$this->RegisterVariableInteger('ManualActivationRunTime', $this->Translate('WF Manual Sprinkler Runtime'),"IC.Timer");
		$this->RegisterVariableBoolean('ManualBlockSprinkler', $this->Translate('WF Manual Sprinkler Block'),"~Switch");
		$this->RegisterVariableBoolean('CurrentRainBlockIrrigation', $this->Translate('Irrigation blocked by rain'));
		$this->RegisterVariableInteger('SoilHumidity', $this->Translate('Soil Humidity'), "IC.SoilHumidity");
		$this->RegisterVariableString('SprinklerDescisionText', $this->Translate('Sprinkler Descision Text'));	
		$this->RegisterVariableInteger('Group1CurrentString', $this->Translate('Group 1 Current String'));
		$this->RegisterVariableBoolean('Group1String1HasRun', $this->Translate('Group 1 String 1 Has Run'));
		$this->RegisterVariableBoolean('Group1String2HasRun', $this->Translate('Group 1 String 2 Has Run'));
		$this->RegisterVariableBoolean('Group1String3HasRun', $this->Translate('Group 1 String 3 Has Run'));
		$this->RegisterVariableBoolean('Group1String4HasRun', $this->Translate('Group 1 String 4 Has Run'));					

	}

	public function ApplyChanges()
	{
			
		//Never delete this line!
		parent::ApplyChanges();
		/*
		$vpos = 10;
		$this->MaintainVariable('Group1CurrentString', $this->Translate('Group 1 Current String'), vtInteger, "", $vpos++, $this->ReadPropertyBoolean("Group1Active") == 1);
		$this->MaintainVariable('Group1String1HasRun', $this->Translate('Group 1 String 1 Has Run'), vtBoolean, "", $vpos++, $this->ReadPropertyBoolean("Group1String1Active") == 1);
		$this->MaintainVariable('Group1String2HasRun', $this->Translate('Group 1 String 2 Has Run'), vtBoolean, "", $vpos++, $this->ReadPropertyBoolean("Group1String2Active") == 1);
		$this->MaintainVariable('Group1String3HasRun', $this->Translate('Group 1 String 3 Has Run'), vtBoolean, "", $vpos++, $this->ReadPropertyBoolean("Group1String3Active") == 1);

		//$this->MaintainVariable('SoilHumidityText', $this->Translate('Soil Humidity Text'), vtString, "", $vpos++, $this->ReadPropertyBoolean("AutoSeason") == 0); //wird immer definiert egal ob humidity sensor oder evapo

		/*
		$vpos = 10;
		$this->RegisterVariableBoolean('AutoSeasonIsSummer', $this->Translate('Automatic season is Summer'));
		
		$this->MaintainVariable('StormVariable', $this->Translate('Storm Warning'), vtBoolean, "~Alert", $vpos++, $this->ReadPropertyBoolean("ProvideStormVariable") == 1);
		$this->MaintainVariable('FrostVariable', $this->Translate('Frost Warning'), vtBoolean, "~Alert", $vpos++, $this->ReadPropertyBoolean("ProvideFrostVariable") == 1);
		$this->MaintainVariable('HeavyRainVariable', $this->Translate('Heavy Rain Warning'), vtBoolean, "~Alert", $vpos++, $this->ReadPropertyBoolean("ProvideHeavyRainVariable") == 1);
		*/

		//$this->WatchDogTimer();
		$ComponentActive = $this->ReadPropertyBoolean("ComponentActive");
		$CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));

		if ($ComponentActive == 1) {
			$this->SetTimerInterval("Watchdog",10000);
		}
		else if ($ComponentActive == 0) {
			$this->SetTimerInterval("Watchdog",0);
		}

		$this->SetResetTimerInterval();
					
	}

	public function StartIrrigation() {
		
		$this->SetResetTimerInterval();
			
		
	}

	public function Watchdog() {
		
		$this->EstimateSoilWetness();	// checks how dry the lawn is
		$this->DisableIrrigationDueToRainForecast(); // evaluates the needed rain in case it is due to rain in x days to deactivate automatic irrigation

		
		//block automatic irrigation due to FUTURE rain by deactivating timer
		$FutureRainBlocksIrrigation = $this->GetBuffer("RainBlocksIrrigation");

		if ($FutureRainBlocksIrrigation == 1) { // enough rain is forecasted ... stop timer for irrigation
			$this->SetTimerInterval("SprinklerOperationGroup1",0);
		}
		else if ($FutureRainBlocksIrrigation == 0) { // no rain is forecasted turn timer back on
			$this->SetResetTimerInterval();
		}


		//block automatic irrigation due to CURRENT rain and turn back on after evaluating preceipt
		//$CurrentRainBlock = GetValue($this->GetIDForIdent("RainBlocksIrrigation"));
		$SensorRain = GetValue($this->ReadPropertyInteger("SensorRain"));
		$CurrentRainBlockIrrigation = GetValue($this->GetIDForIdent("CurrentRainBlockIrrigation"));

		if ($SensorRain == 1  AND $CurrentRainBlockIrrigation ==  0) { // it rains ... stop operation
			$this->SendDebug($this->Translate('Current Rain'),$this->Translate('Rain detected - irrigation is stopped'),0);
			$this->SetTimerInterval("Group1SprinklerStringStop",0); //stops timer
			SetValue($this->GetIDForIdent("CurrentRainBlockIrrigation"), 1);
			$this->Group1SprinklerStringStop();
		}
		else if ($SensorRain == 0 AND $CurrentRainBlockIrrigation == 1) { // rain has stopped ... evaluate if further watering is need by soil humidity or amount of rain fallen
			$this->SendDebug($this->Translate('Current Rain'),$this->Translate('Rain has stopped - irrigation will continue if needed'),0);
			SetValue($this->GetIDForIdent("CurrentRainBlockIrrigation"), 0);
			$this->SprinklerOperationGroup1();
		}


		//set via webfront and will block any sprinkler operation until disabled
		$ManualBlockSprinkler = GetValue($this->GetIDForIdent("ManualBlockSprinkler"));

		if ($ManualBlockSprinkler == 1) { 
			$this->SetTimerInterval("Group1SprinklerStringStop",0); //stops timer
			$this->Group1SprinklerStringStop();
		}
		else if ($ManualBlockSprinkler == 0) {
			
		}
		



		
	}
	
	public function SetResetTimerInterval() {
		$Hour = $this->ReadPropertyInteger("Group1NumberStartHour");
		$Minute = $this->ReadPropertyInteger("Group1NumberStartMinute");
		$Group1ExecutionInterval = $this->ReadPropertyInteger("Group1ExecutionInterval");
		$NewTime = $Hour.":".$Minute;
		$now = new DateTime();
		$target = new DateTime();
		if ($NewTime < date("H:i")) {
			$target->modify('+1 day');
		}
		if ($Group1ExecutionInterval == 1) {
			$target->modify('+'.$Group1ExecutionInterval.' day');
		}
		if ($Group1ExecutionInterval > 1) {
			$target->modify('+'.$Group1ExecutionInterval.' days');
		}
		$target->setTime($Hour, $Minute, 0);
		$diff = $target->getTimestamp() - $now->getTimestamp();
		$Group1Timer = $diff * 1000;
		$this->SetTimerInterval('SprinklerOperationGroup1', $Group1Timer);
	} 



	public function EstimateSoilWetness() {
		$SensorSoilHumidty = GetValue($this->ReadPropertyInteger("SensorSoilHumidity"));
		$EstimateDryoutDryingThreshold = $this->ReadPropertyInteger("EstimateDryoutDryingThreshold");
		$EstimateDryoutDryThreshold = $this->ReadPropertyInteger("EstimateDryoutDryThreshold");

		if ($SensorSoilHumidty < $EstimateDryoutDryingThreshold) {
			//$this->SendDebug($this->Translate('Soil Humidity'),$this->Translate('Soil Humidity Sensor: ').$SensorSoilHumidty.$this->Translate(' cb - translates to soil is wet'),0);
			SetValue($this->GetIDForIdent("SoilHumidity"), 0);
			$this->SetBuffer("SoilHumidity", 0);
		}
		else if ($SensorSoilHumidty >= $EstimateDryoutDryingThreshold AND $SensorSoilHumidty <= $EstimateDryoutDryThreshold) {
			//$this->SendDebug($this->Translate('Soil Humidity'),$this->Translate('Soil Humidity Sensor: ').$SensorSoilHumidty.$this->Translate(' cb - translates to soil is drying out'),0);
			SetValue($this->GetIDForIdent("SoilHumidity"), 1);
			$this->SetBuffer("SoilHumidity", 1);
		}
		else if ($SensorSoilHumidty > $EstimateDryoutDryThreshold) {
			//$this->SendDebug($this->Translate('Soil Humidity'),$this->Translate('Soil Humidity Sensor: ').$SensorSoilHumidty.$this->Translate(' cb - translates to soil is dry'),0);
			SetValue($this->GetIDForIdent("SoilHumidity"), 2);
			$this->SetBuffer("SoilHumidity", 2);
		}
	}


	public function DisableIrrigationDueToRainForecast() {
		$SoilCurrentStatus = $this->GetBuffer("SoilHumidity");
		$EstimateDryoutDryingOutThreshold = $this->ReadPropertyInteger("RainInXDaysMinimumDryingOutThreshold");
		$EstimateDryoutDryingThreshold = $this->ReadPropertyInteger("RainInXDaysMinimumDryThreshold");
		$InformationRainInXDays = GetValue($this->ReadPropertyInteger("InformationRainInXDays"));
	
		switch ($SoilCurrentStatus) {
			case 0: //boden nass
				//$this->SendDebug($this->Translate('Estimated Rain'),$this->Translate('Soil wet - incoming rain will be ignored'),0);
				$this->SetBuffer("FutureRainBlocksIrrigation", 1);
			break;
				
			case 1: //boden trocknet aus
				if ($InformationRainInXDays > $EstimateDryoutDryingOutThreshold ){
					//$this->SendDebug($this->Translate('Estimated Rain'),$this->Translate('Amount: ').$InformationRainInXDays.$this->Translate(' mm - enough to water the soil which is currently drying out'),0);
					$this->SetBuffer("FutureRainBlocksIrrigation", 1);
				}
				else {
					//$this->SendDebug($this->Translate('Estimated Rain'),$this->Translate('Amount: ').$InformationRainInXDays.$this->Translate(' mm - no or not enough rain in the coming days - IRRIGATION NEEDED'),0);
					$this->SetBuffer("FutureRainBlocksIrrigation", 0);
				}
			break;

			case 2: //boden trocken
				if ($InformationRainInXDays > $EstimateDryoutDryingThreshold ){
					//$this->SendDebug($this->Translate('Estimated Rain'),$this->Translate('Amount: ').$InformationRainInXDays.$this->Translate(' mm - enough to water the soil which is currently dry'),0);
					$this->SetBuffer("FutureRainBlocksIrrigation", 1);
				}
				else {
					//$this->SendDebug($this->Translate('Estimated Rain'),$this->Translate('Amount: ').$InformationRainInXDays.$this->Translate(' mm - no or not enough rain in the coming days - IRRIGATION NEEDED'),0);
					$this->SetBuffer("FutureRainBlocksIrrigation", 0);
				}
			break;
		}
	}

	public function RainInLastHour() {
		$archiveID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$SensorRainAmount = $this->ReadPropertyInteger("SensorRainAmount");
		$DelayTime = 3600; //Regen in einer Stunde

			$endtime = time(); // time() for "now"
			$starttime = time()-($DelayTime); // for n minutes ago
			$limit = 0; // kein Limit

			$buffer = AC_GetLoggedValues($archiveID, $SensorRainAmount, $starttime, $endtime, $limit);
			$anzahl = 0;
			$summe = 0;
			foreach ($buffer as $werte){
				$wert = $werte["Value"];
				$anzahl = $anzahl +1;
				$summe = $summe + $wert;
			}
			if ($anzahl > 1) {
				$RainAmount = $summe;
				$this->SendDebug($this->Translate('Rain Amount'),'Rain fall in 1 hour '.$summe.' mm',0);
				$this->SetBuffer("RainAmount", $RainAmount);
			}
			else {
				$this->SendDebug($this->Translate('Rain Amount'),'No rain in past hour',0);
			}

	}


	
	public function SprinklerOperationGroup1() {
		
		$Group1Active = $this->ReadPropertyBoolean("Group1Active"); //On
		$ManualBlockSprinkler = GetValue($this->GetIDForIdent("ManualBlockSprinkler"));

		$Group1String1Active = $this->ReadPropertyBoolean("Group1String1Active"); //On
		$Group1String2Active = $this->ReadPropertyBoolean("Group1String2Active"); //On
		$Group1String3Active = $this->ReadPropertyBoolean("Group1String3Active"); //On
		$Group1String4Active = $this->ReadPropertyBoolean("Group1String4Active"); //On
		
		//Öffnen Hauptventil - wartezeit
		

		$Group1CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));

		$Group1String1HasRun = GetValue($this->GetIDForIdent("Group1String1HasRun"));
		$Group1String2HasRun = GetValue($this->GetIDForIdent("Group1String2HasRun"));
		$Group1String3HasRun = GetValue($this->GetIDForIdent("Group1String3HasRun"));
		$Group1String4HasRun = GetValue($this->GetIDForIdent("Group1String4HasRun"));
		
		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Current String - Entry before reset: '.$Group1CurrentString),0);
		
		if ($Group1CurrentString == 0 AND $Group1String1HasRun == 0 AND $Group1String2HasRun == 0 AND $Group1String3HasRun == 0 AND $Group1String4HasRun == 0) {
			SetValue($this->GetIDForIdent("Group1CurrentString"), 1);
			//unset($Group1String1HasRun);
			//unset($Group1String2HasRun);
			//unset($Group1String3HasRun);
			//unset($Group1String4HasRun);
			$Group1CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));
			$this->SendDebug($this->Translate('Group 1'),$this->Translate('Current String - was 0 and is now '.$Group1CurrentString),0);
			//$this->SprinklerOperationGroup1();
		}
		else {
			//nix
		}
		
		//$Group1CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));

		if ($Group1Active == 1 AND $ManualBlockSprinkler == 0) {
			switch ($Group1CurrentString) {
				case 0:
					$this->SendDebug($this->Translate('Group 1'),$this->Translate('All strings have run - irrigation completed'),0);
					$this->SetTimerInterval("Group1SprinklerStringStop",0); // Stoppt timer
					SetValue($this->GetIDForIdent("Group1String1HasRun"), 0);
					SetValue($this->GetIDForIdent("Group1String2HasRun"), 0);
					SetValue($this->GetIDForIdent("Group1String3HasRun"), 0);
					SetValue($this->GetIDForIdent("Group1String4HasRun"), 0);
					SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
				break;
				case 1:
					if ($Group1String1Active == 1 AND $Group1String1HasRun == 0) {
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('String 1 is triggered to start watering'),0);
						$this->SetBuffer("Group1StringValve1", $this->ReadPropertyInteger("Group1String1Valve1")); //Bolean Var on/off
						$this->SetBuffer("Group1StringValve2", $this->ReadPropertyInteger("Group1String1Valve2")); //Bolean Var on/off
						$this->SetBuffer("Group1StringStringTime", $this->ReadPropertyInteger("Group1String1Time")); //Time to Water
						//$this->SetBuffer("Group1StringLiterPerHour", GetValue($this->ReadPropertyInteger("String1LiterPerHour"))); //Info to countup 
						SetValue($this->GetIDForIdent("Group1CurrentString"), 1);
						$this->SetBuffer("Group1CurrentString", 1);
						$this->Group1SprinklerStringStart();
					}
					if ($Group1String1Active == 0)  {
						SetValue($this->GetIDForIdent("Group1CurrentString"), 2);
						$this->SprinklerOperationGroup1();
					}
				break;
				case 2:
					if ($Group1String2Active == 1 AND $Group1String2HasRun == 0){
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('String 2 is triggered to start watering'),0);
						$this->SetBuffer("Group1StringValve1", $this->ReadPropertyInteger("Group1String2Valve1")); //Bolean Var on/off
						$this->SetBuffer("Group1StringValve2", $this->ReadPropertyInteger("Group1String2Valve2")); //Bolean Var on/off
						$this->SetBuffer("Group1StringStringTime", $this->ReadPropertyInteger("Group1String2Time")); //Time to Water
						//$this->SetBuffer("Group1StringLiterPerHour", GetValue($this->ReadPropertyInteger("String1LiterPerHour"))); //Info to countup 
						SetValue($this->GetIDForIdent("Group1CurrentString"), 2);
						$this->SetBuffer("Group1CurrentString", 2);
						$this->Group1SprinklerStringStart();
					}
					if ($Group1String2Active == 0) {
						SetValue($this->GetIDForIdent("Group1CurrentString"), 3);
						$this->SprinklerOperationGroup1();
					}
					// Variablen zusammenstellen und laufen lassen
				break;
				case 3:
					if ($Group1String3Active == 1 AND $Group1String3HasRun == 0){
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('String 3 is triggered to start watering'),0);
						$this->SetBuffer("Group1StringValve1", $this->ReadPropertyInteger("Group1String3Valve1")); //Bolean Var on/off
						$this->SetBuffer("Group1StringValve2", $this->ReadPropertyInteger("Group1String3Valve2")); //Bolean Var on/off
						$this->SetBuffer("Group1StringStringTime", $this->ReadPropertyInteger("Group1String3Time")); //Time to Water
						//$this->SetBuffer("Group1StringLiterPerHour", GetValue($this->ReadPropertyInteger("String1LiterPerHour"))); //Info to countup 
						SetValue($this->GetIDForIdent("Group1CurrentString"), 3);
						$this->SetBuffer("Group1CurrentString", 3);
						$this->Group1SprinklerStringStart();
					}
					if ($Group1String3Active == 0) {
						SetValue($this->GetIDForIdent("Group1CurrentString"), 4);
						$this->SprinklerOperationGroup1();
					}
				break;
				case 4:
					if ($Group1String4Active == 1 AND $Group1String4HasRun == 0){
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('String 4 is triggered to start watering'),0);
						$this->SetBuffer("Group1StringValve1", $this->ReadPropertyInteger("Group1String4Valve1")); //Bolean Var on/off
						$this->SetBuffer("Group1StringValve2", $this->ReadPropertyInteger("Group1String4Valve2")); //Bolean Var on/off
						$this->SetBuffer("Group1StringStringTime", $this->ReadPropertyInteger("Group1String4Time")); //Time to Water
						//$this->SetBuffer("Group1StringLiterPerHour", GetValue($this->ReadPropertyInteger("String1LiterPerHour"))); //Info to countup 
						SetValue($this->GetIDForIdent("Group1CurrentString"), 4);
						$this->SetBuffer("Group1CurrentString", 4);
						$this->Group1SprinklerStringStart();
					}
					if ($Group1String4Active == 0) {
						SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
						$this->SprinklerOperationGroup1();
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('F1'),0);
					}
				break;			
			} 
		}
		else {
			$this->SendDebug($this->Translate('Group 1'),$this->Translate('Currently disabled or manually blocked by setting in Webfront'),0);
			SetValue($this->GetIDForIdent("Group1String1HasRun"), 0);
			SetValue($this->GetIDForIdent("Group1String2HasRun"), 0);
			SetValue($this->GetIDForIdent("Group1String3HasRun"), 0);
			SetValue($this->GetIDForIdent("Group1String4HasRun"), 0);
			SetValue($this->GetIDForIdent("Group1CurrentString"), 1);
			$this->SendDebug($this->Translate('Group 1'),$this->Translate('F2'),0);
		}


	}

	public function Group1SprinklerStringStart(){

		$this->SendDebug($this->Translate('Group 1'),$this->Translate('**********************************'),0);		
		$StringValve1 = $this->GetBuffer("Group1StringValve1");
		$StringValve2 = $this->GetBuffer("Group1StringValve2");
		$StringTime = $this->GetBuffer("Group1StringStringTime");
		$StringLiterPerHour = $this->GetBuffer("Group1StringLiterPerHour");
		$CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));

		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Current String - Start Section: '.$CurrentString),0);
		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Start Valve 1 ID '.$StringValve1),0);
		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Start Valve 2 ID '.$StringValve2),0);


		if ($StringValve1 != 0) {
			SetValue($StringValve1,1);
		}

		if ($StringValve2 != 0) {
			SetValue($StringValve2,1);
		}
		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Timer: '.$StringTime.' for String '.$CurrentString),0);
		$StringRunTime = $StringTime * 10000;
		$this->SetTimerInterval("Group1SprinklerStringStop",$StringRunTime);
		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Timer set'),0);

	}


	public function Group1SprinklerStringStop(){

		$this->SendDebug($this->Translate('Group 1'),$this->Translate('**********************************'),0);		
		$CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));
		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Current String - Stop: '.$CurrentString),0);
		$StringValve1 = $this->GetBuffer("Group1StringValve1");
		$StringValve2 = $this->GetBuffer("Group1StringValve2");

		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Current String - Stop Sprinkler: '.$CurrentString),0);		
		if ($StringValve1 != 0) {
			SetValue($StringValve1,0);
		}

		if ($StringValve2 != 0) {
			SetValue($StringValve2,0);
		}

		$this->SetTimerInterval("Group1SprinklerStringStop",0); // Stoppt timer

	
		$this->SendDebug($this->Translate('Group 1'),$this->Translate('String '.$CurrentString.' has stopped watering'),0);

		switch ($CurrentString) {
		case 1:
			SetValue($this->GetIDForIdent("Group1String1HasRun"), 1);
			SetValue($this->GetIDForIdent("Group1CurrentString"), 2);
			$this->SprinklerOperationGroup1();
		break;

		case 2:
			SetValue($this->GetIDForIdent("Group1String2HasRun"), 1);
			SetValue($this->GetIDForIdent("Group1CurrentString"), 3);
			$this->SprinklerOperationGroup1();
		break;

		case 3:
			SetValue($this->GetIDForIdent("Group1String3HasRun"), 1);
			SetValue($this->GetIDForIdent("Group1CurrentString"), 4);
			$this->SprinklerOperationGroup1();
		break;

		case 4:
			SetValue($this->GetIDForIdent("Group1String4HasRun"), 1);
			SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
			$this->SprinklerOperationGroup1();
		break;
		}
		

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
