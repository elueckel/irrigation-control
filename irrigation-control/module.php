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
		$this->RegisterPropertyInteger("SensorHumidity",0);
		$this->RegisterPropertyInteger("SensorWind",0);
		$this->RegisterPropertyInteger("InformationRainInXDays",0);
		
		//Configuration
		$this->RegisterPropertyString("MethodToEstimateDryout", 1); //Soil humidity = default ... maybe in the future Evotranspiration


		//Definitions
		//Humidity
		$this->RegisterPropertyBoolean("HumiditySensorActive", 0);
		$this->RegisterPropertyInteger("SensorSoilHumidity", 0);
		$this->RegisterPropertyInteger("EstimateDryoutDryingThreshold", 20);
		$this->RegisterPropertyInteger("EstimateDryoutDryThreshold", 50);

		$this->RegisterPropertyInteger("RainInXDaysMinimumDryingOutThreshold", 15);
		$this->RegisterPropertyInteger("RainInXDaysMinimumDryThreshold", 40);

		$this->RegisterPropertyBoolean("WriteToLog",0);
		$this->RegisterPropertyBoolean("Notification",0); //To be fill with warning messages

		//Gruppe 1
		$this->RegisterPropertyBoolean("Group1Active", 0);
		$this->RegisterPropertyInteger("Group1NumberStartHour","22");
		$this->RegisterPropertyInteger("Group1NumberStartMinute","00");
		$this->RegisterPropertyInteger("Group1AutomaticActivationThresholdHumidity", 0);
		$this->RegisterPropertyInteger("Group1ExecutionInterval",0);
		//$this->RegisterPropertyInteger("Group1OperationStartHour","22");
		//$this->RegisterPropertyInteger("Group1OperationStartMinute","00");
		//$this->RegisterPropertyInteger("Group1OperationEndHour","06");
		//$this->RegisterPropertyInteger("Group1OperationEndMinute","00");
		

		//Group 1
		$this->RegisterPropertyBoolean("Group1MasterValveActive", 0);
		$this->RegisterPropertyInteger("Group1MasterValve1", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1MasterValve2", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1MasterValveWaitTime", 0); //in Minutes

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

		$this->RegisterPropertyBoolean("Group1String5Active", 0);
		$this->RegisterPropertyInteger("Group1String5Valve1", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String5Valve2", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String5Time", 0); //in Minutes
		$this->RegisterPropertyInteger("Group1String5LiterPerHour", 0); //l

		$this->RegisterPropertyBoolean("Group1String6Active", 0);
		$this->RegisterPropertyInteger("Group1String6Valve1", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String6Valve2", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("Group1String6Time", 0); //in Minutes
		$this->RegisterPropertyInteger("Group1String6LiterPerHour", 0); //l


		//timer stuff
		$this->RegisterPropertyBoolean("ComponentActive", 0);
		$this->RegisterPropertyInteger("Hour","03");
		$this->RegisterPropertyInteger("Minute","00");
		
		//Properties
		$this->RegisterTimer('SprinklerOperationGroup1', 0, 'IC_SprinklerOperationGroup1($_IPS["TARGET"]);'); //Test
		$this->RegisterTimer('Watchdog', 0, 'IC_Watchdog($_IPS["TARGET"]);'); //Timer to monitor things and perform frequent tasks
		$this->RegisterTimer('Evapotranspiration', 0, 'IC_Evapotranspiration($_IPS["TARGET"]);'); //Timer to monitor soil humidity and collect weather data
		$this->RegisterTimer('Group1SprinklerStringStop', 0, 'IC_Group1SprinklerStringStop($_IPS["TARGET"]);'); //Timer starting Irrigation

		//Variables always needed

		// https://www.irrometer.com/basics.html
		if (IPS_VariableProfileExists("IC.SoilHumidity") == false) {
			IPS_CreateVariableProfile("IC.SoilHumidity", 1);
			IPS_SetVariableProfileIcon("IC.SoilHumidity", "Drops");
			IPS_SetVariableProfileAssociation("IC.SoilHumidity", 0, $this->Translate("Wet"), "", -1);
			IPS_SetVariableProfileAssociation("IC.SoilHumidity", 1, $this->Translate("Drying Out"), "", -1);
			IPS_SetVariableProfileAssociation("IC.SoilHumidity", 2, $this->Translate("Dry"), "", -1);
		}

		if (IPS_VariableProfileExists("IC.CurrentStatus") == false) {
			IPS_CreateVariableProfile("IC.CurrentStatus", 1);
			IPS_SetVariableProfileIcon("IC.CurrentStatus", "Gear");
			IPS_SetVariableProfileAssociation("IC.CurrentStatus", 0, $this->Translate("Inactive"), "", -1);
			IPS_SetVariableProfileAssociation("IC.CurrentStatus", 1, $this->Translate("Running Automatically"), "", -1);
			IPS_SetVariableProfileAssociation("IC.CurrentStatus", 2, $this->Translate("Running Manually"), "", -1);
		}

		if (IPS_VariableProfileExists("IC.ManualGroup") == false) {
			IPS_CreateVariableProfile("IC.ManualGroup", 1);
			IPS_SetVariableProfileIcon("IC.ManualGroup", "Gear");
			IPS_SetVariableProfileAssociation("IC.ManualGroup", 1, $this->Translate("Group 1"), "", -1);
			IPS_SetVariableProfileAssociation("IC.ManualGroup", 2, $this->Translate("Group 2"), "", -1);
		}
		
		if (IPS_VariableProfileExists("IC.StringRun") == false) {
			IPS_CreateVariableProfile("IC.StringRun", 1);
			IPS_SetVariableProfileIcon("IC.StringRun", "Gear");
			IPS_SetVariableProfileAssociation("IC.StringRun", 0, $this->Translate("No"), "", -1);
			IPS_SetVariableProfileAssociation("IC.StringRun", 1, $this->Translate("Yes"), "", -1);
			IPS_SetVariableProfileAssociation("IC.StringRun", 2, $this->Translate("Runnig"), "", -1);
		}
		
		if (IPS_VariableProfileExists("IC.MasterValve") == false) {
			IPS_CreateVariableProfile("IC.MasterValve", 0);
			IPS_SetVariableProfileIcon("IC.MasterValve", "Drops");
			IPS_SetVariableProfileAssociation("IC.MasterValve", 0, $this->Translate("Closed"), "", -1);
			IPS_SetVariableProfileAssociation("IC.MasterValve", 1, $this->Translate("Open"), "", -1);
		}


		if (IPS_VariableProfileExists("IC.GroupAutomaticActivation") == false) {
			IPS_CreateVariableProfile("IC.GroupAutomaticActivation", 1);
			IPS_SetVariableProfileIcon("IC.GroupAutomaticActivation", "Robot");
			IPS_SetVariableProfileAssociation("IC.GroupAutomaticActivation", 0, $this->Translate("No Automation"), "", -1);
			IPS_SetVariableProfileAssociation("IC.GroupAutomaticActivation", 1, $this->Translate("Auto Enabled"), "", -1);
			IPS_SetVariableProfileAssociation("IC.GroupAutomaticActivation", 2, $this->Translate("Auto Disabled"), "", -1);
		}

		if (IPS_VariableProfileExists("IC.ManualString") == false) {
			IPS_CreateVariableProfile("IC.ManualString", 1);
			IPS_SetVariableProfileIcon("IC.ManualString", "Gear");
			IPS_SetVariableProfileAssociation("IC.ManualString", 0, $this->Translate("Alle Strings"), "", -1);
			IPS_SetVariableProfileAssociation("IC.ManualString", 1, $this->Translate("String 1"), "", -1);
			IPS_SetVariableProfileAssociation("IC.ManualString", 2, $this->Translate("String 2"), "", -1);
			IPS_SetVariableProfileAssociation("IC.ManualString", 3, $this->Translate("String 3"), "", -1);
			IPS_SetVariableProfileAssociation("IC.ManualString", 4, $this->Translate("String 4"), "", -1);
			IPS_SetVariableProfileAssociation("IC.ManualString", 5, $this->Translate("String 5"), "", -1);
			IPS_SetVariableProfileAssociation("IC.ManualString", 6, $this->Translate("String 6"), "", -1);
		}
		
		if (IPS_VariableProfileExists("IC.Timer") == false) {
			IPS_CreateVariableProfile("IC.Timer", 1);
			IPS_SetVariableProfileIcon("IC.Timer", "Clock");
			IPS_SetVariableProfileDigits("IC.Timer", 0);
			IPS_SetVariableProfileValues("IC.Timer", 0, 60, 1);
		}

		$this->RegisterVariableBoolean('ManualActivationSprinkler', $this->Translate('WF Manual Sprinkler Activation'),"~Switch");		
		$this->RegisterVariableInteger('ManualActivationRunTime', $this->Translate('WF Manual Sprinkler Runtime'),"IC.Timer");
		$this->RegisterVariableInteger('ManualActivationGroup', $this->Translate('WF Manual Sprinkler Group'),"IC.ManualGroup");
		$this->RegisterVariableInteger('ManualActivationString', $this->Translate('WF Manual Sprinkler String'),"IC.ManualString");
		$this->RegisterVariableBoolean('ManualBlockSprinkler', $this->Translate('WF Manual Sprinkler Block'),"~Switch");
		$this->RegisterVariableInteger('Group1CurrentStatus', $this->Translate('Group 1 Current Status'),"IC.CurrentStatus");
		$this->RegisterVariableBoolean('CurrentRainBlockIrrigation', $this->Translate('Irrigation blocked by rain'), "~Switch");
		$this->RegisterVariableInteger('Group1AutomaticActivation', $this->Translate('Group 1 Automation'),"IC.GroupAutomaticActivation");				
		$this->RegisterVariableInteger('SoilHumidity', $this->Translate('Soil Humidity'), "IC.SoilHumidity");
		$this->RegisterVariableFloat('Evapotranspiration', $this->Translate('Evapotranspiration Grass'),"~Rainfall");
		//$this->RegisterVariableString('SprinklerDescisionText', $this->Translate('Sprinkler Descision Text'));	
		$this->RegisterVariableInteger('Group1CurrentString', $this->Translate('Group 1 Current String'));
		$this->RegisterVariableBoolean('Group1MasterValve1', $this->Translate('Group 1 Master Valve 1'),"IC.MasterValve");
		$this->RegisterVariableBoolean('Group1MasterValve2', $this->Translate('Group 1 Master Valve 2'),"IC.MasterValve");
		$this->RegisterVariableInteger('Group1String1HasRun', $this->Translate('Group 1 String 1 Has Run'),"IC.StringRun");
		$this->RegisterVariableInteger('Group1String2HasRun', $this->Translate('Group 1 String 2 Has Run'),"IC.StringRun");
		$this->RegisterVariableInteger('Group1String3HasRun', $this->Translate('Group 1 String 3 Has Run'),"IC.StringRun");
		$this->RegisterVariableInteger('Group1String4HasRun', $this->Translate('Group 1 String 4 Has Run'),"IC.StringRun");
		$this->RegisterVariableInteger('Group1String5HasRun', $this->Translate('Group 1 String 5 Has Run'),"IC.StringRun");					
		$this->RegisterVariableInteger('Group1String6HasRun', $this->Translate('Group 1 String 6 Has Run'),"IC.StringRun");
		
	}

	public function ApplyChanges()
	{
			
		//Never delete this line!
		parent::ApplyChanges();
				
		$ComponentActive = $this->ReadPropertyBoolean("ComponentActive");
		$CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));

		if ($ComponentActive == 1) {
			$this->SetTimerInterval("Watchdog",10000);
			$this->SetTimerInterval("Evapotranspiration",18000000);
			$now = new DateTime();
			$target = new DateTime();
			if ("14:00:00" < date("H:i")) {
				$target->modify('+1 day');
			}
			$target->setTime(14, 00, 0);
			$diff = $target->getTimestamp() - $now->getTimestamp();
			$EvaTimer = $diff * 1000;
			$this->SetTimerInterval('Evapotranspiration', $EvaTimer);
		}
		else if ($ComponentActive == 0) {
			$this->SetTimerInterval("Watchdog",0);
			$this->SetTimerInterval("Evapotranspiration",0);
		}

		$this->SetResetTimerInterval();

	}

	public function Evapotranspiration() {

		$temp=GetValue($this->ReadPropertyInteger("SensorTemperature"));
		$feucht=GetValue($this->ReadPropertyInteger("SensorHumidity"));

		$z1=7.602*$temp;
		$z2=241.2+$temp;
		$satt = 6.11213 * pow(10,($z1/$z2)); //Sttigungsdampfdruck
		$dampf = ($satt * $feucht) / 100;  //Wasserdampfdruck
		$sattdef = $satt - $dampf;    //Sttigungsdefizit
		$verdunst = 0.24 * $sattdef;  //Verdunstungsrate pro Tag
		$verdunst = round($verdunst, 2);
		if ($verdunst > 7) {
		$verdunst = 7;
		}
		SetValue($this->GetIDForIdent("Evapotranspiration"), (FLOAT)$verdunst);

	}

	public function Watchdog() {

		$Notification = $this->ReadPropertyBoolean("Notification");
		$WriteToLog = $this->ReadPropertyBoolean("WriteToLog");
		$CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));
		
		$this->DisableIrrigationDueToRainForecast(); // evaluates the needed rain in case it is due to rain in x days to deactivate automatic irrigation
		$this->RainInLastHour();
		$this->AutomaticActivationDeactivation();
		$this->EstimateSoilWetness();
		
		$SensorRain = GetValue($this->ReadPropertyInteger("SensorRain"));
		$CurrentRainBlockIrrigation = GetValue($this->GetIDForIdent("CurrentRainBlockIrrigation"));
		$Group1CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));

		if ($SensorRain == 1  AND $CurrentRainBlockIrrigation ==  0 AND $CurrentString > 0) { // it rains ... stop operation
			$this->SendDebug($this->Translate('Current Rain'),$this->Translate('Rain detected - irrigation is stopped'),0);
			$this->SetTimerInterval("Group1SprinklerStringStop",0); //stops timer
			SetValue($this->GetIDForIdent("CurrentRainBlockIrrigation"), 1);
			$this->SetBuffer("RainStoppedAtGroup1String", $Group1CurrentString);
			//SetValue($this->GetIDForIdent("SprinklerDescisionText"),"Irrigation stopped due to rain at String: ".$Group1CurrentString,0);
			$this->Group1SprinklerStringStop();
		}
		else if ($SensorRain == 0 AND $CurrentRainBlockIrrigation == 1 AND $CurrentString > 0) { // rain has stopped ... evaluate if further watering is need by soil humidity or amount of rain fallen
			$this->SendDebug($this->Translate('Current Rain'),$this->Translate('************************************'),0);
			SetValue($this->GetIDForIdent("CurrentRainBlockIrrigation"), 0);
			$CurrentRainBlocksIrrigation = $this->GetBuffer("CurrentRainBlocksIrrigation");

			if ($CurrentRainBlocksIrrigation == 1) {
				$this->SendDebug($this->Translate('Current Rain'),$this->Translate('Rain has stopped - no further irrigation needed'),0);
				SetValue($this->GetIDForIdent("Group1CurrentString"), 0); // places current string into waiting state = 0
				if ($WriteToLog == 1) {
					IPS_LogMessage("Beregnungssteuerung", "Regen hat aufgehört - Beregnung wird aufgrund von ausreichend Regen nicht fortgesetzt");							
				}
				$this->SprinklerOperationGroup1();
			}
			else if ($CurrentRainBlocksIrrigation == 0 ) {
				//Get from buffer where irrigation stopped
				$this->SendDebug($this->Translate('Current Rain'),$this->Translate('Rain has stopped - not enough rain - irrigation will continue'),0);
				$RainStoppedAtGroup1String = $this->GetBuffer("RainStoppedAtGroup1String");
				SetValue($this->GetIDForIdent("Group1CurrentString"), $RainStoppedAtGroup1String);
				if ($WriteToLog == 1) {
					IPS_LogMessage("Beregnungssteuerung", "Regen hat aufgehört - Es hat nicht ausreichend geregnet um Boden zu bewässern, Beregnung wird fortgesetzt");						
				}
				$this->SprinklerOperationGroup1();
			}
		}
		

		//set via webfront and will block any sprinkler operation until disabled
		//**********************************************************************

		$ManualBlockSprinkler = GetValue($this->GetIDForIdent("ManualBlockSprinkler"));
		
		if ($ManualBlockSprinkler == 1) { 
			$this->SetTimerInterval("Group1SprinklerStringStop",0); //stops timer
			/*
			if ($WriteToLog == 1 AND $LogManualBlockSprinkler == 0 ) {
				IPS_LogMessage("Beregnungssteuerung", "!!! Manuelle Blockade der Beregnung - alle Vorgänge wurden unterbrochen");
				$LogManualBlockSprinkler = 1;
			}
			*/
			$this->Group1SprinklerStringStop();
		}
		else if ($ManualBlockSprinkler == 0) {
			/*
			if ($WriteToLog == 1 AND $LogManualBlockSprinkler == 1) {
				IPS_LogMessage("Beregnungssteuerung", "!!! Manuelle Blockade der Beregnung aufgehoben");
				$LogManualBlockSprinkler = 0;
			}
			*/
		}


		//manually start sprinkler via webfront
		//*************************************

		$ManualActivationSprinkler = GetValue($this->GetIDForIdent("ManualActivationSprinkler"));
		$ManualActivationRunTime = GetValue($this->GetIDForIdent("ManualActivationRunTime"));
		$ManualActivationGroup = GetValue($this->GetIDForIdent("ManualActivationGroup"));
		$ManualActivationString = GetValue($this->GetIDForIdent("ManualActivationString"));
		$Group1CurrentStatus = GetValue($this->GetIDForIdent("Group1CurrentStatus"));
				
		if ($ManualActivationSprinkler == 1){
			if ($ManualActivationGroup == 1) {
				if ($ManualActivationString == 0) {
					//starte bei String 0
					SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
					$this->SetBuffer("Group1ActivationManual", 1);
					$this->SetBuffer("Group1ActivationManualTimer", $ManualActivationRunTime);
					if ($WriteToLog == 1) {
						IPS_LogMessage("Beregnungssteuerung", "!!! Manueller Start der Beregnung - Alle Abschnitte werden für ".$ManualActivationRunTime." Minuten beregnet");
					}
					$this->SendDebug($this->Translate('Manual Activation'),$this->Translate('All Strings'),0);
					SetValue($this->GetIDForIdent("Group1CurrentStatus"), 2);
					SetValue($this->GetIDForIdent("ManualActivationSprinkler"), 0);
					$this->SprinklerOperationGroup1();
				}
				else if ($ManualActivationString > 0) { //will only run 1 specific string
					SetValue($this->GetIDForIdent("Group1CurrentString"), $ManualActivationString);
					$this->SetBuffer("Group1ManualActivationSingleString", 1);
					$this->SetBuffer("Group1ActivationManual", 1);
					$this->SetBuffer("Group1ActivationManualTimer", $ManualActivationRunTime);
					SetValue($this->GetIDForIdent("ManualActivationSprinkler"), 0); // in case irrigation was manually started
					if ($WriteToLog == 1) {
						IPS_LogMessage("Beregnungssteuerung", "!!! Manueller Start der Beregnung - Abschnitt ".$ManualActivationString." wird für ".$ManualActivationRunTime." Minuten beregnet");
					}
					//$this->SendDebug($this->Translate('Manual Activation'),$this->Translate('Single String Number: ').$ManualActivationString,0);
					SetValue($this->GetIDForIdent("Group1CurrentStatus"), 2);
					$this->SprinklerOperationGroup1();
				}
			}
			// Activation for Group 2 goes here
		}
		
		else if ($ManualActivationSprinkler == 0 AND $Group1CurrentStatus == 0){ // set manual buffer variables with 0 to avoid empty values
			$this->SetBuffer("Group1ManualActivationSingleString", 0);
			$this->SetBuffer("Group1ActivationManual", 0);
			$this->SetBuffer("Group1ActivationManualTimer", 0);
		}
		

		
	}
	

	//function enables or disables automatic execution of watering
	//************************************************************

	public function AutomaticActivationDeactivation() {

		$Notification = $this->ReadPropertyBoolean("Notification");
		$WriteToLog = $this->ReadPropertyBoolean("WriteToLog");

		$DescissionSoilHumidity = $this->GetBuffer("SoilHumidity"); //0 Wet, 1 Drying out, 2 dry
		$DescissionFutureRainBlocksIrrigation = $this->GetBuffer("FutureRainBlocksIrrigation");
		
		$Group1AutomaticActivationThresholdHumidity = $this->ReadPropertyInteger("Group1AutomaticActivationThresholdHumidity");
		$Group1AutomaticActivationThresholdHumidityCurrentStatus = GetValue($this->GetIDForIdent("Group1AutomaticActivation"));
		$SensorSoilHumidity = GetValue($this->ReadPropertyInteger("SensorSoilHumidity"));

		if ($Group1AutomaticActivationThresholdHumidity == 0) {
			//Automatic activation of Group 1 is disabled
			//$this->SendDebug($this->Translate('Automation'),$this->Translate('Automatic Group Activation disabled'),0);
			SetValue($this->GetIDForIdent("Group1AutomaticActivation"), 0);
			$Group1AutomaticMode = 0;
		}
		else if ($Group1AutomaticActivationThresholdHumidity !== 0) {
			if ($SensorSoilHumidity > $Group1AutomaticActivationThresholdHumidity) {
				//$this->SendDebug($this->Translate('Automation'),$this->Translate('Automatic Group Activation enabled and turned ON since above threshold'),0);
				if ($Group1AutomaticActivationThresholdHumidityCurrentStatus !== 1) {
					if ($WriteToLog == 1) {
						//IPS_LogMessage("Beregnungssteuerung", "Gruppe 1 automatisch aktiviert - Bodenfeuchte ".$SensorSoilHumidity." Schwellwert ".$Group1AutomaticActivationThresholdHumidity);
					}
					if ($Notification == 1) {
						$this->SetBuffer("NotifierTitle", "Beregnungssteuerung");
						$this->SetBuffer("NotifierMessage", "Gruppe 1 automatisch aktiviert");
						$this->NotifyApp();
					}
				}
				$Group1AutomaticMode = 1;
				SetValue($this->GetIDForIdent("Group1AutomaticActivation"), 1);
				//Soil in Group 1 is above group threshold => Turn on
			}
			else if ($SensorSoilHumidity <= $Group1AutomaticActivationThresholdHumidity) {
				//$this->SendDebug($this->Translate('Automation'),$this->Translate('Automatic Group Activation enabled and turned OFF since below threshold'),0);
				if ($Group1AutomaticActivationThresholdHumidityCurrentStatus !== 2) {
					if ($WriteToLog == 1) {
						//IPS_LogMessage("Beregnungssteuerung", "Gruppe 1 automatisch deaktiviert - Bodenfeuchte ".$SensorSoilHumidity." Schwellwert ".$Group1AutomaticActivationThresholdHumidity);
					}
					if ($Notification == 1) {
						$this->SetBuffer("NotifierTitle", "Beregnungssteuerung");
						$this->SetBuffer("NotifierMessage", "Gruppe 1 automatisch deaktiviert");
						$this->NotifyApp();
					}
				}
				$Group1AutomaticMode = 2;
				SetValue($this->GetIDForIdent("Group1AutomaticActivation"), 2);
				//Soil in Group 1 is below group threshold => Turn off
			}
		}


		if ($DescissionSoilHumidity == 0) { //soil is wet - no irrigation needed
			//$this->SendDebug($this->Translate('Automation'),$this->Translate('Soil is wet ... no irrigation needed'),0);
			$this->SetTimerInterval('SprinklerOperationGroup1', 0);
		}
		else if ($DescissionSoilHumidity > 0) {
			if ($DescissionFutureRainBlocksIrrigation == 0 AND $Group1AutomaticMode == 1) { //soil is dry - no rain inbound => irrigate
				//$this->SendDebug($this->Translate('Automation'),$this->Translate('Soil is dry ... automatic irrigation turned on group activation ENABLED'),0);
				$this->SetResetTimerInterval();
			}
			if ($DescissionFutureRainBlocksIrrigation == 1 OR $Group1AutomaticMode == 2) {  //soil is dry - rain is inbound => stop irrigation
				//$this->SendDebug($this->Translate('Automation'),$this->Translate('Soil is dry ... rain is inbound or automatic group activation disabled => stop irrigation'),0);
				$this->SetTimerInterval('SprinklerOperationGroup1', 0);
			}
		}

	}
	

	//function sets timer for Group1 
	//******************************

	public function SetResetTimerInterval() {
		$Group1Active = $this->ReadPropertyBoolean("Group1Active");

		if ($Group1Active == 1) {
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
		else if ($Group1Active == 0) {
			$this->SetTimerInterval('SprinklerOperationGroup1', 0);
		}
	} 


	//function check how dry the soil is
	//**********************************

	public function EstimateSoilWetness() {

		$Notification = $this->ReadPropertyBoolean("Notification");
		$WriteToLog = $this->ReadPropertyBoolean("WriteToLog");

		$SensorSoilHumidty = GetValue($this->ReadPropertyInteger("SensorSoilHumidity"));
		$EstimateDryoutDryingThreshold = $this->ReadPropertyInteger("EstimateDryoutDryingThreshold");
		$EstimateDryoutDryThreshold = $this->ReadPropertyInteger("EstimateDryoutDryThreshold");
		$CurrentStatusSoilHumidity = GetValue($this->GetIDForIdent("SoilHumidity"));
		
		$Group1AutomaticActivationThresholdHumidity = $this->ReadPropertyInteger("Group1AutomaticActivationThresholdHumidity");

		if ($SensorSoilHumidty < $EstimateDryoutDryingThreshold) {
			//$this->SendDebug($this->Translate('Soil Humidity'),$this->Translate('Soil Humidity Sensor: ').$SensorSoilHumidty.$this->Translate(' cb - translates to soil is wet'),0);
			if ($CurrentStatusSoilHumidity !== 0) {
				SetValue($this->GetIDForIdent("SoilHumidity"), 0);
				if ($WriteToLog == 1) {
					IPS_LogMessage("Beregnungssteuerung", "Bodenfeuchte hat gewechselt auf feucht");
				}
				if ($Notification == 1) {
					$this->SetBuffer("NotifierTitle", "Beregnungssteuerung");
					$this->SetBuffer("NotifierMessage", "Bodenfeuchte hat gewechselt auf feucht");
					$this->NotifyApp();
				}
			}
			$this->SetBuffer("SoilHumidity", 0); //Sprinkler Mode??
		}
		else if ($SensorSoilHumidty >= $EstimateDryoutDryingThreshold AND $SensorSoilHumidty <= $EstimateDryoutDryThreshold) {
			//$this->SendDebug($this->Translate('Soil Humidity'),$this->Translate('Soil Humidity Sensor: ').$SensorSoilHumidty.$this->Translate(' cb - translates to soil is drying out'),0);
			if ($CurrentStatusSoilHumidity !== 1) {
				SetValue($this->GetIDForIdent("SoilHumidity"), 1);
				if ($WriteToLog == 1) {
					IPS_LogMessage("Beregnungssteuerung", "Bodenfeuchte hat gewechselt auf trocknet aus");
				}
				if ($Notification == 1) {
					$this->SetBuffer("NotifierTitle", "Beregnungssteuerung");
					$this->SetBuffer("NotifierMessage", "Bodenfeuchte hat gewechselt auf trocknet aus");
					$this->NotifyApp();
				}
			}	
			$this->SetBuffer("SoilHumidity", 1);
		}
		else if ($SensorSoilHumidty > $EstimateDryoutDryThreshold) {
			//$this->SendDebug($this->Translate('Soil Humidity'),$this->Translate('Soil Humidity Sensor: ').$SensorSoilHumidty.$this->Translate(' cb - translates to soil is dry'),0);
			if ($CurrentStatusSoilHumidity !== 2) {
				SetValue($this->GetIDForIdent("SoilHumidity"), 2);
				if ($WriteToLog == 1) {
					IPS_LogMessage("Beregnungssteuerung", "Bodenfeuchte hat gewechselt auf trocken");
				}
				if ($Notification == 1) {
					$this->SetBuffer("NotifierTitle", "Beregnungssteuerung");
					$this->SetBuffer("NotifierMessage", "Bodenfeuchte hat gewechselt auf trocken");
					$this->NotifyApp();
				}
			}
			$this->SetBuffer("SoilHumidity", 2);
		}
	}

	//function disables irrigation when rain starts 
	//*********************************************

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


	// Checks if enough rain has fallen to interupt irrigation
	//********************************************************

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
				//$this->SendDebug($this->Translate('Rain Amount'),'Rain fall in 1 hour '.$summe.' mm',0);
				$this->SetBuffer("RainAmount", $RainAmount);
			}
			else {
				$RainAmount = 0;
				//$this->SendDebug($this->Translate('Rain Amount'),'No rain in past hour',0);
			}

		$SoilCurrentStatus = $this->GetBuffer("SoilHumidity");
		$EstimateDryoutDryingOutThreshold = $this->ReadPropertyInteger("RainInXDaysMinimumDryingOutThreshold");
		$EstimateDryoutDryingThreshold = $this->ReadPropertyInteger("RainInXDaysMinimumDryThreshold");
		
	
		switch ($SoilCurrentStatus) {
			case 0: //boden nass
				$this->SendDebug($this->Translate('Fallen Rain'),$this->Translate('Soil wet - rain will be ignored'),0);
				$this->SetBuffer("CurrentRainBlocksIrrigation", 1);
			break;
				
			case 1: //boden trocknet aus
				if ($RainAmount > $EstimateDryoutDryingOutThreshold ){
					//$this->SendDebug($this->Translate('Fallen Rain'),$this->Translate('Amount: ').$RainAmount.$this->Translate(' mm - enough to water the soil which is currently drying out'),0);
					$this->SetBuffer("CurrentRainBlocksIrrigation", 1);
				}
				else {
					//$this->SendDebug($this->Translate('Fallen Rain'),$this->Translate('Amount: ').$RainAmount.$this->Translate(' mm - no or not enough rain in the coming days - IRRIGATION NEEDED'),0);
					$this->SetBuffer("CurrentRainBlocksIrrigation", 0);
				}
			break;

			case 2: //boden trocken
				if ($RainAmount > $EstimateDryoutDryingThreshold ){
					//$this->SendDebug($this->Translate('Fallen Rain'),$this->Translate('Amount: ').$RainAmount.$this->Translate(' mm - enough to water the soil which is currently dry'),0);
					$this->SetBuffer("CurrentRainBlocksIrrigation", 1);
				}
				else {
					//$this->SendDebug($this->Translate('Fallen Rain'),$this->Translate('Amount: ').$RainAmount.$this->Translate(' mm - no or not enough rain in the coming days - IRRIGATION NEEDED'),0);
					$this->SetBuffer("CurrentRainBlocksIrrigation", 0);
				}
			break;
		}

	}

	//function which is sending push messages in case it is activated
	//***************************************************************

	public function NotifyApp() {
		$NotifierTitle = $this->GetBuffer("NotifierTitle");
		$NotifierMessage = $this->GetBuffer("NotifierMessage");
		$WebFrontMobile = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}')[0];
		// to send notifications
		$this->SendDebug("Notifier","********** App Notifier **********", 0);
		$this->SendDebug("Notifier","Message: ".$NotifierMessage." was sent", 0);			
		WFC_PushNotification($WebFrontMobile, $NotifierTitle, $NotifierMessage , "", 0);
	}

	// Section controlling Spprinkler Group 1 ... activation happens via Sart Script / Stop via timed stop script
	//***********************************************************************************************************
	
	public function SprinklerOperationGroup1() {
		
		$Group1Active = $this->ReadPropertyBoolean("Group1Active"); //On
		$ManualBlockSprinkler = GetValue($this->GetIDForIdent("ManualBlockSprinkler"));

		$Notification = $this->ReadPropertyBoolean("Notification");
		$WriteToLog = $this->ReadPropertyBoolean("WriteToLog");

		$Group1String1Active = $this->ReadPropertyBoolean("Group1String1Active"); //On
		$Group1String2Active = $this->ReadPropertyBoolean("Group1String2Active"); //On
		$Group1String3Active = $this->ReadPropertyBoolean("Group1String3Active"); //On
		$Group1String4Active = $this->ReadPropertyBoolean("Group1String4Active"); //On
		$Group1String5Active = $this->ReadPropertyBoolean("Group1String5Active"); //On
		$Group1String6Active = $this->ReadPropertyBoolean("Group1String6Active"); //On
		
		//Öffnen Hauptventil - wartezeit
		

		$Group1CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));

		$Group1String1HasRun = GetValue($this->GetIDForIdent("Group1String1HasRun"));
		$Group1String2HasRun = GetValue($this->GetIDForIdent("Group1String2HasRun"));
		$Group1String3HasRun = GetValue($this->GetIDForIdent("Group1String3HasRun"));
		$Group1String4HasRun = GetValue($this->GetIDForIdent("Group1String4HasRun"));
		$Group1String5HasRun = GetValue($this->GetIDForIdent("Group1String5HasRun"));
		$Group1String6HasRun = GetValue($this->GetIDForIdent("Group1String6HasRun"));
		
		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Current String - Entry before reset: '.$Group1CurrentString),0);
		
		if ($Group1CurrentString == 0 AND $Group1String1HasRun == 0 AND $Group1String2HasRun == 0 AND $Group1String3HasRun == 0 AND $Group1String4HasRun == 0 AND $Group1String5HasRun == 0 AND $Group1String6HasRun == 0) {
			SetValue($this->GetIDForIdent("Group1CurrentString"), 1);
			if ($WriteToLog == 1) {
				IPS_LogMessage("Beregnungssteuerung", "Automatischer Start der Beregnung - Gruppe 1");
			}
			if ($Notification == 1) {
				$this->SetBuffer("NotifierTitle", "Beregnung");
				$this->SetBuffer("NotifierMessage", "Beregnung automatisch gestartet");
				$this->NotifyApp();
			}
			//unset($Group1String1HasRun);
			//unset($Group1String2HasRun);
			//unset($Group1String3HasRun);
			//unset($Group1String4HasRun);
			//unset($Group1String5HasRun);
			//unset($Group1String6HasRun);
			$Group1CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));
			$this->SendDebug($this->Translate('Group 1'),$this->Translate('Current String - was 0 and is now '.$Group1CurrentString),0);
			//$this->SprinklerOperationGroup1();
		}
		else {
			//nix
		}
		
		if ($Group1Active == 1 AND $ManualBlockSprinkler == 0) {
			
			$Group1MasterValve1 = $this->ReadPropertyInteger("Group1MasterValve1"); //Bolean Var on/off
			$Group1MasterValve2 = $this->ReadPropertyInteger("Group1MasterValve2"); //Bolean Var on/off
			//$Group1MasterValveWaitTime = $this->ReadPropertyInteger("Group1MasterValveWaitTime");
			
			if ($Group1MasterValve1 != 0) {
				$Group1MasterValve1Var = GetValue($this->GetIDForIdent("Group1MasterValve1"));
				if ($Group1MasterValve1Var == 0) {
					$this->SendDebug($this->Translate('Group 1'),$this->Translate('Master Valves 1 opened'),0);
					//SetValue($Group1MasterValve1,1);
					HM_WriteValueBoolean($Group1MasterValve1,"STATE", 1);
					SetValue($this->GetIDForIdent("Group1MasterValve1"), 1);
					$MasterValveWaitTimeActive = 1;
				}
			}
	
			if ($Group1MasterValve2 != 0) {
				$Group1MasterValve2Var = GetValue($this->GetIDForIdent("Group1MasterValve2"));
				if ($Group1MasterValve2Var == 0) {
					$this->SendDebug($this->Translate('Group 1'),$this->Translate('Master Valves 2 opened'),0);
					//SetValue($Group1MasterValve2,1);
					HM_WriteValueBoolean($Group1MasterValve1,"STATE", 1);
					SetValue($this->GetIDForIdent("Group1MasterValve2"), 1);
					$MasterValveWaitTimeActive = 1;
				}
			}


			switch ($Group1CurrentString) {
				case 0:
					$this->SendDebug($this->Translate('Group 1'),$this->Translate('All strings have run - irrigation completed'),0);
					$this->SetTimerInterval("Group1SprinklerStringStop",0); // Stoppt timer
					SetValue($this->GetIDForIdent("Group1CurrentStatus"), 0); // Sets Sprinkler Status back to 0 - off
					SetValue($this->GetIDForIdent("Group1String1HasRun"), 0);
					SetValue($this->GetIDForIdent("Group1String2HasRun"), 0);
					SetValue($this->GetIDForIdent("Group1String3HasRun"), 0);
					SetValue($this->GetIDForIdent("Group1String4HasRun"), 0);
					SetValue($this->GetIDForIdent("Group1String5HasRun"), 0);
					SetValue($this->GetIDForIdent("Group1String6HasRun"), 0);
					if ($Group1MasterValve1 != 0) {
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('Master Valves 1 closed'),0);
						SetValue($this->GetIDForIdent("Group1MasterValve1"), 0);
						//SetValue($Group1MasterValve1,0);
						HM_WriteValueBoolean($Group1MasterValve1,"STATE", 0);
					}
					if ($Group1MasterValve2 != 0) {
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('Master Valves 2 closed'),0);
						SetValue($this->GetIDForIdent("Group1MasterValve2"), 0);
						//SetValue($Group1MasterValve2,0);
						HM_WriteValueBoolean($Group1MasterValve2, "STATE", 0);
					}
					SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
					SetValue($this->GetIDForIdent("ManualActivationSprinkler"), 0);
					if ($WriteToLog == 1) {
						IPS_LogMessage("Beregnungssteuerung", "Automatischer Stop der Beregnung - Gruppe 1");
					}
					if ($Notification == 1) {
						$this->SetBuffer("NotifierTitle", "Beregnung");
						$this->SetBuffer("NotifierMessage", "Beregnung automatisch beendet");
						$this->NotifyApp();
					}
				break;
				case 1:
					if ($Group1String1Active == 1 AND $Group1String1HasRun == 0) {
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('String 1 is triggered to start watering'),0);
						$this->SetBuffer("Group1StringValve1", $this->ReadPropertyInteger("Group1String1Valve1")); //Bolean Var on/off
						$this->SetBuffer("Group1StringValve2", $this->ReadPropertyInteger("Group1String1Valve2")); //Bolean Var on/off
						$this->SetBuffer("Group1StringStringTime", $this->ReadPropertyInteger("Group1String1Time")); //Time to Water
						//$this->SetBuffer("Group1StringLiterPerHour", GetValue($this->ReadPropertyInteger("String1LiterPerHour"))); //Info to countup 
						SetValue($this->GetIDForIdent("Group1CurrentString"), 1);
						SetValue($this->GetIDForIdent("Group1String1HasRun"), 2);
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
						SetValue($this->GetIDForIdent("Group1String2HasRun"), 2);
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
						SetValue($this->GetIDForIdent("Group1String3HasRun"), 2);
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
						SetValue($this->GetIDForIdent("Group1String4HasRun"), 2);
						$this->SetBuffer("Group1CurrentString", 4);
						$this->Group1SprinklerStringStart();
					}
					if ($Group1String4Active == 0) {
						SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
						$this->SprinklerOperationGroup1();
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('F1'),0);
					}
				break;
				case 5:
					if ($Group1String5Active == 1 AND $Group1String5HasRun == 0){
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('String 5 is triggered to start watering'),0);
						$this->SetBuffer("Group1StringValve1", $this->ReadPropertyInteger("Group1String5Valve1")); //Bolean Var on/off
						$this->SetBuffer("Group1StringValve2", $this->ReadPropertyInteger("Group1String5Valve2")); //Bolean Var on/off
						$this->SetBuffer("Group1StringStringTime", $this->ReadPropertyInteger("Group1String5Time")); //Time to Water
						//$this->SetBuffer("Group1StringLiterPerHour", GetValue($this->ReadPropertyInteger("String1LiterPerHour"))); //Info to countup 
						SetValue($this->GetIDForIdent("Group1CurrentString"), 5);
						SetValue($this->GetIDForIdent("Group1String5HasRun"), 2);
						$this->SetBuffer("Group1CurrentString", 5);
						$this->Group1SprinklerStringStart();
					}
					if ($Group1String5Active == 0) {
						SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
						$this->SprinklerOperationGroup1();
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('F1'),0);
					}
				break;
				case 6:
					if ($Group1String6Active == 1 AND $Group1String6HasRun == 0){
						$this->SendDebug($this->Translate('Group 1'),$this->Translate('String 6 is triggered to start watering'),0);
						$this->SetBuffer("Group1StringValve1", $this->ReadPropertyInteger("Group1String6Valve1")); //Bolean Var on/off
						$this->SetBuffer("Group1StringValve2", $this->ReadPropertyInteger("Group1String6Valve2")); //Bolean Var on/off
						$this->SetBuffer("Group1StringStringTime", $this->ReadPropertyInteger("Group1String6Time")); //Time to Water
						//$this->SetBuffer("Group1StringLiterPerHour", GetValue($this->ReadPropertyInteger("String1LiterPerHour"))); //Info to countup 
						SetValue($this->GetIDForIdent("Group1CurrentString"), 6);
						SetValue($this->GetIDForIdent("Group1String6HasRun"), 2);
						$this->SetBuffer("Group1CurrentString", 6);
						$this->Group1SprinklerStringStart();
					}
					if ($Group1String6Active == 0) {
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
			SetValue($this->GetIDForIdent("Group1String5HasRun"), 0);
			SetValue($this->GetIDForIdent("Group1String6HasRun"), 0);
			SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
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
		$Group1ActivationManual = $this->GetBuffer("Group1ActivationManual");
		$Group1ActivationManualTimer = $this->GetBuffer("Group1ActivationManualTimer");

		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Current String - Start Section: '.$CurrentString),0);
		//$this->SendDebug($this->Translate('Group 1'),$this->Translate('Start Valve 1 ID '.$StringValve1),0);
		//$this->SendDebug($this->Translate('Group 1'),$this->Translate('Start Valve 2 ID '.$StringValve2),0);


		if ($StringValve1 != 0) {
			HM_WriteValueBoolean($StringValve1,"STATE", 1);
		}

		if ($StringValve2 != 0) {
			HM_WriteValueBoolean($StringValve2,"STATE", 1);
			//SetValue($StringValve2,1);
		}

		if ($Group1ActivationManual == 0) {
			$this->SendDebug($this->Translate('Group 1'),$this->Translate('Automatic Timer: '.$StringTime.' for String '.$CurrentString),0);
			$StringRunTime = $StringTime * 60000;
			SetValue($this->GetIDForIdent("Group1CurrentStatus"), 1);
			$this->SetTimerInterval("Group1SprinklerStringStop",$StringRunTime);
		}
		else if ($Group1ActivationManual == 1) {
			$this->SendDebug($this->Translate('Group 1'),$this->Translate('Manual Timer: '.$StringTime.' for String '.$CurrentString),0);
			$StringRunTime = $Group1ActivationManualTimer * 60000;
			SetValue($this->GetIDForIdent("Group1CurrentStatus"), 2);
			$this->SetTimerInterval("Group1SprinklerStringStop",$StringRunTime);
		}
		//$this->SendDebug($this->Translate('Group 1'),$this->Translate('Timer set'),0);

	}


	public function Group1SprinklerStringStop(){

		$this->SendDebug($this->Translate('Group 1'),$this->Translate('**********************************'),0);		
		$CurrentString = GetValue($this->GetIDForIdent("Group1CurrentString"));
		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Current String - Stop: '.$CurrentString),0);
		$StringValve1 = $this->GetBuffer("Group1StringValve1");
		$StringValve2 = $this->GetBuffer("Group1StringValve2");
		$Group1ManualActivationSingleString = $this->GetBuffer("Group1ManualActivationSingleString");

		$this->SendDebug($this->Translate('Group 1'),$this->Translate('Current String - Stop Sprinkler: '.$CurrentString),0);		
		if ($StringValve1 != 0) {
			//SetValue($StringValve1,0);
			HM_WriteValueBoolean($StringValve1,"STATE", 0);
		}

		if ($StringValve2 != 0) {
			//SetValue($StringValve2,0);
			HM_WriteValueBoolean($StringValve2,"STATE", 0);
		}

		$this->SetTimerInterval("Group1SprinklerStringStop",0); // Stoppt timer

	
		$this->SendDebug($this->Translate('Group 1'),$this->Translate('String '.$CurrentString.' has stopped watering'),0);
		$this->SendDebug($this->Translate('Manual Activation'),$this->Translate('Status Manual Activation ').$Group1ManualActivationSingleString,0);
		

		switch ($CurrentString) {
		case 1:
			SetValue($this->GetIDForIdent("Group1String1HasRun"), 1);
			if ($Group1ManualActivationSingleString == 0) {
				SetValue($this->GetIDForIdent("Group1CurrentString"), 2);
			}
			else if ($Group1ManualActivationSingleString == 1) {
				SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
			}
			$this->SprinklerOperationGroup1();
		break;

		case 2:
			SetValue($this->GetIDForIdent("Group1String2HasRun"), 1);
			if ($Group1ManualActivationSingleString == 0) {
				SetValue($this->GetIDForIdent("Group1CurrentString"), 3);
			}
			else if ($Group1ManualActivationSingleString == 1) {
				SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
			}
			$this->SprinklerOperationGroup1();
		break;

		case 3:
			SetValue($this->GetIDForIdent("Group1String3HasRun"), 1);
			if ($Group1ManualActivationSingleString == 0) {
				SetValue($this->GetIDForIdent("Group1CurrentString"), 4);
			}
			else if ($Group1ManualActivationSingleString == 1) {
				SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
			}
			$this->SprinklerOperationGroup1();
		break;

		case 4:
			SetValue($this->GetIDForIdent("Group1String4HasRun"), 1);
			if ($Group1ManualActivationSingleString == 0) {
				SetValue($this->GetIDForIdent("Group1CurrentString"), 5);
			}
			else if ($Group1ManualActivationSingleString == 1) {
				SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
			}
			$this->SprinklerOperationGroup1();
		break;
		case 5:
			SetValue($this->GetIDForIdent("Group1String5HasRun"), 1);
			if ($Group1ManualActivationSingleString == 0) {
				SetValue($this->GetIDForIdent("Group1CurrentString"), 6);
			}
			else if ($Group1ManualActivationSingleString == 1) {
				SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
			}
			$this->SprinklerOperationGroup1();
		break;
		case 6:
			SetValue($this->GetIDForIdent("Group1String6HasRun"), 1);
			if ($Group1ManualActivationSingleString == 0) { //no real use ... just in case more string should be added
				SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
			}
			else if ($Group1ManualActivationSingleString == 1) {
				SetValue($this->GetIDForIdent("Group1CurrentString"), 0);
			}
			$this->SprinklerOperationGroup1();
		break;
		}

	}

}
