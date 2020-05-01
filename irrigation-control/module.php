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
		$this->RegisterPropertyInteger("SensorSoilHumidity", 0);
		$this->RegisterPropertyInteger("SensorRain",0);
		$this->RegisterPropertyInteger("SensorTemperature",0);
		$this->RegisterPropertyInteger("SensorWind",0);
		$this->RegisterPropertyInteger("InformationRainInXDays",0)
		
		//Configuration
		$this->RegisterPropertyInteger("MethodToEstimateDryout", 1); //Soil humidity = default ... maybe in the future Evotranspiration


		//Definitions
		$this->RegisterPropertyInteger("EstimateDryoutDryingThreshold", 20);
		$this->RegisterPropertyInteger("EstimateDryoutDryThreshold", 50);

		$this->RegisterPropertyInteger("RainInXDaysMinimumDryingOutThreshold", 15);
		$this->RegisterPropertyInteger("RainInXDaysMinimumDryThreshold", 40);

		//Gruppe 1
		$this->RegisterPropertyBoolean("Group1Active", 0);
		$this->RegisterPropertyInteger("Group1StartTime","22");
		//$this->RegisterPropertyInteger("Group1NumberStartMinute","00");
		$this->RegisterPropertyInteger("Group1OperationStartHour","22");
		$this->RegisterPropertyInteger("Group1OperationStartMinute","00");
		$this->RegisterPropertyInteger("Group1OperationEndHour","06");
		$this->RegisterPropertyInteger("Group1OperationEndMinute","00");
		

		//String 1
		$this->RegisterPropertyBoolean("String1Active", 0);
		$this->RegisterPropertyInteger("String1Valve1", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("String1Valve2", 0); //Boolean Var on/off
		$this->RegisterPropertyInteger("String1Time", 0); //in Minutes
		$this->RegisterPropertyInteger("String1LiterPerHour", 0); //l



		//timer stuff
		$this->RegisterPropertyBoolean("ComponentActive", 0);
		$this->RegisterPropertyInteger("Hour","11");
		$this->RegisterPropertyInteger("Minute","00");
		
		//Properties
		$this->RegisterTimer('SprinklerOperationGroup1', 0, 'IC_SprinklerOperationGroup1($_IPS["TARGET"]);'); //Test
		//$this->RegisterTimer('Watchdog', 0, 'IC_Watchdog($_IPS["TARGET"]);'); //Timer to monitor things and perform frequent tasks
		$this->RegisterTimer('Group1SprinklerStringStop', 0, 'IC_Group1SprinklerStringStop($_IPS["TARGET"]);'); //Timer starting Irrigation

		//Variables always needed

		if (IPS_VariableProfileExists("IC.SoilHumidity") == false) {
			IPS_CreateVariableProfile("IC.SoilHumidity", 1);
			IPS_SetVariableProfileIcon("IC.SoilHumidity", "Shutter");
			IPS_SetVariableProfileAssociation("IC.SoilHumidity", 0, $this->Translate("Wet"), "", 0xffffff);
			IPS_SetVariableProfileAssociation("IC.SoilHumidity", 1, $this->Translate("Drying Out"), "", 0xffffff);
			IPS_SetVariableProfileAssociation("IC.SoilHumidity", 2, $this->Translate("Dry"), "", 0xffffff);
		}

		$this->RegisterVariableInteger('SoilHumidity', $this->Translate('Soil Humidity'), "SoilHumidity");
		$this->RegisterVariableString('SprinklerDescisionText', $this->Translate('Sprinkler Descision Text'));			

	}

	public function ApplyChanges()
	{
			
		//Never delete this line!
		parent::ApplyChanges();
		
		

		//$this->MaintainVariable('SoilHumidityText', $this->Translate('Soil Humidity Text'), vtString, "", $vpos++, $this->ReadPropertyBoolean("AutoSeason") == 0); //wird immer definiert egal ob humidity sensor oder evapo

		/*
		$vpos = 10;
		$this->RegisterVariableBoolean('AutoSeasonIsSummer', $this->Translate('Automatic season is Summer'));
		
		$this->MaintainVariable('StormVariable', $this->Translate('Storm Warning'), vtBoolean, "~Alert", $vpos++, $this->ReadPropertyBoolean("ProvideStormVariable") == 1);
		$this->MaintainVariable('FrostVariable', $this->Translate('Frost Warning'), vtBoolean, "~Alert", $vpos++, $this->ReadPropertyBoolean("ProvideFrostVariable") == 1);
		$this->MaintainVariable('HeavyRainVariable', $this->Translate('Heavy Rain Warning'), vtBoolean, "~Alert", $vpos++, $this->ReadPropertyBoolean("ProvideHeavyRainVariable") == 1);
		*/


		$this->SetResetTimerInterval();
					
	}
	

	public function StartIrrigation() {
		
		$this->SetResetTimerInterval();	
		
	}

	public function Watchdog() {
		
		$this->EstimateSoilWetness();	//checks how dry the lawn is
		
	}
		
	private function EstimateSoilWetness() {
		$SensorSoilHumidty = GetValue($this->ReadPropertyInteger("SensorSoilHumidity"));
		$EstimateDryoutDryingThreshold = $this->ReadPropertyInteger("EstimateDryoutDryingThreshold");
		$EstimateDryoutDryThreshold = $this->ReadPropertyInteger("EstimateDryoutDryThreshold");

		if ($SensorSoilHumidty < $EstimateDryoutDryingThreshold) {
			$this->SendDebug($this->Translate('Soil Humidity'),$this->Translate('Soil Humidity Sensor: ').$SensorSoilHumidty.$this->Translate(' cb - translates to soil is wet'),0);
			SetValue($this->GetIDForIdent("SoilHumidity"), 0);
			$this->SetBuffer("SoilHumidity", 1);
		}
		else if ($SensorSoilHumidty >= $EstimateDryoutDryingThreshold AND $SensorSoilHumidty <= $EstimateDryoutDryThreshold) {
			$this->SendDebug($this->Translate('Soil Humidity'),$this->Translate('Soil Humidity Sensor: ').$SensorSoilHumidty.$this->Translate(' cb - translates to soil is drying out'),0);
			SetValue($this->GetIDForIdent("SoilHumidity"), 1);
			$this->SetBuffer("SoilHumidity", 1);
		}
		else if ($SensorSoilHumidty > $EstimateDryoutDryThreshold) {
			$this->SendDebug($this->Translate('Soil Humidity'),$this->Translate('Soil Humidity Sensor: ').$SensorSoilHumidty.$this->Translate(' cb - translates to soil is dry'),0);
			SetValue($this->GetIDForIdent("SoilHumidity"), 2);
			$this->SetBuffer("SoilHumidity", 2);
		}
	}

	private function EstimateNeededRain() {
		$SoilCurrentStatus = $this->GetBuffer("SoilHumidity");
		$EstimateDryoutDryingThreshold = $this->ReadPropertyInteger("RainInXDaysMinimumDryingOutThreshold");
		$EstimateDryoutDryingThreshold = $this->ReadPropertyInteger("RainInXDaysMinimumDryThreshold");
		$InformationRainInXDays = GetValue($this->ReadPropertyInteger("InformationRainInXDays"));

		switch ($SoilCurrentStatus) {
			case 0: //boden nass
				$this->SendDebug($this->Translate('Estimated Rain'),$this->Translate('Soil wet - incoming rain will be ignored'),0);
				$this->SetBuffer("RainBlocksIrrigation", 1);
			break;
				
			case 1: //boden trocknet aus
				if ($InformationRainInXDays > $EstimateDryoutDryingThreshold ){
					$this->SendDebug($this->Translate('Estimated Rain'),$this->Translate('Amount: ').$InformationRainInXDays.$this->Translate(' mm - enough to water the soil which is currently drying out'),0);
					$this->SetBuffer("RainBlocksIrrigation", 1);
				}
				else {
					$this->SendDebug($this->Translate('Estimated Rain'),$this->Translate('Amount: ').$InformationRainInXDays.$this->Translate(' mm - no or not enough rain in the coming days - IRRIGATION NEEDED'),0);
					$this->SetBuffer("RainBlocksIrrigation", 0);
				}
			break;

			case 2: //boden trocken
				if ($InformationRainInXDays > $EstimateDryoutDryingThreshold ){
					$this->SendDebug($this->Translate('Estimated Rain'),$this->Translate('Amount: ').$InformationRainInXDays.$this->Translate(' mm - enough to water the soil which is currently dry'),0);
					$this->SetBuffer("RainBlocksIrrigation", 1);
				}
				else {
					$this->SendDebug($this->Translate('Estimated Rain'),$this->Translate('Amount: ').$InformationRainInXDays.$this->Translate(' mm - no or not enough rain in the coming days - IRRIGATION NEEDED'),0);
					$this->SetBuffer("RainBlocksIrrigation", 0);
				}
			break;
		}
	}

	
	private function SprinklerOperationGroup1() {
		//switches single sprinklers in Group1 on and off - controller by timer ... e.g. 3:00

		/*
		$this->RegisterPropertyBoolean("Group1Active", 0);
		$this->RegisterPropertyInteger("Group1NumberStartHour","22");
		$this->RegisterPropertyInteger("Group1NumberStartMinute","00");
		$this->RegisterPropertyInteger("Group1NumberOperationStartHour","22");
		$this->RegisterPropertyInteger("Group1NumberOperationStartMinute","00");
		$this->RegisterPropertyInteger("Group1NumberOperationEndHour","06");
		$this->RegisterPropertyInteger("Group1NumberOperationEndMinute","00");


		NumberOfStrings 3
		CurrentString 1
		BlockString = 0 //druch wind oder Regen
		TimeString1 == 15 min
		TimeString2 == 20 min
		Begin Auführung
		Ende Ausführung

		$Group1String1Active = GetValue($this->ReadPropertyInteger("String1Active")); //On

		$Group1String1Active = GetValue($this->ReadPropertyInteger("String1Active")); //On
		$Group1String1Active = GetValue($this->ReadPropertyInteger("String1Active")); //On
		$Group1String1Active = GetValue($this->ReadPropertyInteger("String1Active")); //On
		$Group1String1Active = GetValue($this->ReadPropertyInteger("String1Active")); //On
		$Group1String1Active = GetValue($this->ReadPropertyInteger("String1Active")); //On


		Öffnen Hauptventil - wartezeit
		*/

		switch ($CurrentString) {
			case 1:
				if ($Group1String1Active == 1 AND Group1String1HasRun != 1) {
					$this->SendDebug($this->Translate('Group 1'),$this->Translate('String 1 is triggered to start watering'),0);
					SetBuffer("Group1StringValve1", GetValue($this->ReadPropertyInteger("String1Valve1")); //Bolean Var on/off
					SetBuffer("Group1StringValve2", GetValue($this->ReadPropertyInteger("String1Valve2")); //Bolean Var on/off
					SetBuffer("Group1StringString1Time", GetValue($this->ReadPropertyInteger("String1Time")); //Time to Water
					SetBuffer("Group1StringLiterPerHour", GetValue($this->ReadPropertyInteger("String1LiterPerHour")); //Info to countup 
					SetBuffer("Group1CurrentString", 1);
				}
				else {
					SetBuffer("CurrentString1", 2);
				}

			case 2:
				if ($Group1String1Active == 1 AND Group1String2HasRun != 1){

				}
				else {
					SetBuffer("CurrentString1", 3);
				}
				// Variablen zusammenstellen und laufen lassen

			case 3:
				if ($Group1String1Active == 1 AND Group1String3HasRun != 1)
				// Variablen zusammenstellen und laufen lassen
			
			case 4:
				// Alle Sprinkler sind gelaufen
			break;
		} 


	}


	private function Group1SprinklerStringStart(){

		$StringValve1 = $this->GetBuffer("Group1StringValve1");
		$StringValve2 = $this->GetBuffer("Group1StringValve2");
		$StringTime = $this->GetBuffer("Group1StringString1Time");
		$StringLiterPerHour = $this->GetBuffer("Group1StringLiterPerHour");
		$CurrentString = $this->GetBuffer("Group1CurrentString");

		if ($StringValve1 != 0) {
			SetValue($StringValve1,1);
		}

		if ($StringValve2 != 0) {
			SetValue($StringValve2,1);
		}
		
		$StringRunTime = $this->ReadPropertyInteger("$StringTime") * 1000;
		$this->SetTimerInterval("Group1SprinklerStringStop",$StringRunTime);

	}


	private function Group1SprinklerStringStop(){

		$CurrentString = $this->GetBuffer("Group1CurrentString");

		if ($StringValve1 != 0) {
			SetValue($StringValve1,0);
		}

		if ($StringValve2 != 0) {
			SetValue($StringValve2,0);
		}

		$this->SetTimerInterval("Group1SprinklerStringStop",0);

		SetBuffer("Group1CurrentString", $CurrentString+1);
		$this->SprinklerOperationGroup1();

	}






	//hier vom Notebook die aktuellen Timer einfügen	
	public function SetResetTimerInterval() {

		$Group1StartTime = $this->ReadPropertyString("Group1StartTime");
		//$Minute = $this->ReadPropertyString("Group1NumberStartMinute");
		
		$now = new DateTime();
		$target = new DateTime();
		$target->modify('+1 day');
		$target->setTime($Group1StartTime);
		$diff = $target->getTimestamp() - $now->getTimestamp();
		$interval = $diff * 1000;
		$this->SetTimerInterval('SprinklerOperationGroup1', $interval);
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
