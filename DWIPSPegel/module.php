<?php /** @noinspection PhpExpressionResultUnusedInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);
	class DWIPSPegel extends IPSModule
	{
        public function Create()
		{
			//Never delete this line!
			parent::Create();

            //$this->RegisterPropertyString("water", "");
            $this->RegisterAttributeString("waterAtt", "");
            $this->RegisterAttributeString("levelAtt", "");
            $this->RegisterAttributeBoolean("logging", true);
            $this->RegisterAttributeString("unit", "");
            $this->RegisterAttributeInteger("interval", 0);

            if(!IPS_VariableProfileExists("DWIPS.Pegel.Strecke.m")){
                IPS_CreateVariableProfile("DWIPS.Pegel.Strecke.m", 2);
                IPS_SetVariableProfileText("DWIPS.Pegel.Strecke.m", "", " m");

            }
            if(!IPS_VariableProfileExists("DWIPS.Pegel.Tendenz")){
                IPS_CreateVariableProfile("DWIPS.Pegel.Tendenz", 1);
                IPS_SetVariableProfileAssociation("DWIPS.Pegel.Tendenz", -1, "sinkend", "", -1);
                IPS_SetVariableProfileAssociation("DWIPS.Pegel.Tendenz", 0, "gleichbleibend", "", -1);
                IPS_SetVariableProfileAssociation("DWIPS.Pegel.Tendenz", 1, "steigend", "", -1);
            }

            $this->RegisterTimer("UpdateTimer", 0, "DWIPSPEGEL_UpdateCurrent(".$this->InstanceID.");");

        }

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

        public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}

        public function GetConfigurationForm()
        {
            $selectedWater = $this->ReadAttributeString("waterAtt");
            $selectedLevel= $this->ReadAttributeString("levelAtt");
            $logging= $this->ReadAttributeBoolean("logging");

            $jsonForm = json_decode(file_get_contents(__DIR__ . "/form.json"), true);

            $waters_URL = "https://pegelonline.wsv.de/webservices/rest-api/v2/waters.json";
            $waters_json = file_get_contents($waters_URL);
            $waters = json_decode($waters_json, true);

            $waterOptions = array();
            $waterArray = array("caption" => "", "value" => "");
            $waterOptions[] = $waterArray;
            foreach ($waters as $water){
                $waterArray = array("caption" => $water["longname"], "value" => $water["longname"]);
                $waterOptions[] = $waterArray;
            }
            $jsonForm["elements"][0]["options"] = $waterOptions;
            $jsonForm["elements"][0]["value"] = $selectedWater;

            $waterLevels_URL = "https://pegelonline.wsv.de/webservices/rest-api/v2/stations.json";
            if($this->ReadAttributeString("waterAtt") <> ""){
                $waterLevels_URL .= "?waters=" . $selectedWater;
            }

            $waterLevels_json = file_get_contents($waterLevels_URL);
            $levels = json_decode($waterLevels_json, true);
            $levelOptions = array();
            $levelArray = array("caption" => "", "value" => "");
            $levelOptions[] = $levelArray;
            foreach ($levels as $level){
                $levelArray = array("caption" => $level["longname"], "value" => $level["uuid"]);
                $levelOptions[] = $levelArray;
            }
            $jsonForm["elements"][1]["options"] = $levelOptions;
            $jsonForm["elements"][1]["value"] = $selectedLevel;

            if($selectedLevel <> ""){
                $jsonForm["elements"][2]["visible"] = true;
                $jsonForm["elements"][2]["value"] = $logging;

                $jsonForm["actions"][0]["visible"] = $logging;
            }

            $jsonForm["elements"][4]["value"] = $this->ReadAttributeInteger("interval");



            return json_encode($jsonForm);
        }

        public function ReloadConfigurationForm(){
            $this->ReloadForm();
        }

        public function WriteAttributeWaterAtt(string $val){
            /** @noinspection PhpExpressionResultUnusedInspection */
            $this->WriteAttributeString("waterAtt", $val);
        }

        public function changeLevel(string $level){
            $this->WriteAttributeString("levelAtt", $level);

            if($level == ""){
                $this->MaintainVariable("current", "Aktueller Wert", 2, "DWIPS.Pegel.Strecke.m", 1, false);
                $this->MaintainVariable("lat", "Breitengrad", 2, "", 10, false);
                $this->MaintainVariable("long", "Längengrad", 2, "", 11, false);
                $this->MaintainVariable("tendency", "Tendenz", 1, "", 2, false);
            }else{
                $this->MaintainVariable("current", "Aktueller Wert", 2, "DWIPS.Pegel.Strecke.m", 1, true);
                $this->MaintainVariable("lat", "Breitengrad", 2, "", 10, true);
                $this->MaintainVariable("long", "Längengrad", 2, "", 11, true);
                $this->MaintainVariable("tendency", "Tendenz", 1, "DWIPS.Pegel.Tendenz", 3, true);
                $this->MaintainVariable("leveltimestamp", "Pegelzeit", 1,"~UnixTimestamp", 2,true);


                $level_URL = "https://pegelonline.wsv.de/webservices/rest-api/v2/stations/" . "$level" . ".json?includeTimeseries=true&includeCurrentMeasurement=true&includeCharacteristicValues=true";
                $level_json = file_get_contents($level_URL);
                $levelData = json_decode($level_json, true);
                $timeseries = $levelData["timeseries"];
                $wseries = array();
                $qseries = array();
                foreach($timeseries as $ts){
                    switch ($ts["shortname"]){
                        case "W":
                            $wseries = $ts;
                            break;
                        case "Q":
                            $qseries = $ts;
                            break;
                        default:
                            break;
                    }
                }
                $this->SendDebug("Form",print_r($wseries["currentMeasurement"], true),0);
                $unitDiv = 1.0;
                switch($wseries["unit"]){
                    case "dm":
                        $unitDiv = 10.0;
                        break;
                    case "cm":
                        $unitDiv = 100.0;
                        break;
                    case "mm":
                        $unitDiv = 1000.0;
                        break;
                    default:
                        break;
                }
                $oldCurrent = $this->GetValue("current");
                $newCurrent = $wseries["currentMeasurement"]["value"]/$unitDiv;
                $this->SetValue("current", $newCurrent);
                $diffCurrent = $newCurrent - $oldCurrent;
                $this->SetValue("tendency", $diffCurrent/abs($diffCurrent));
                $this->SetValue("leveltimestamp", strtotime($wseries["currentMeasurement"]["timestamp"]));
                $this->SetValue("lat", $levelData["latitude"]);
                $this->SetValue("long", $levelData["longitude"]);
                $this->WriteAttributeInteger("interval", $wseries["equidistance"]);
                $this->SetTimerInterval("UpdateTimer", $this->ReadAttributeInteger("interval")*60000);

                $chartID = IPS_CreateMedia(4);
                IPS_SetParent($chartID, $this->InstanceID);

            }
        }

        /**
         * @param bool $logging
         * @return void
         */
        public function changeLogging(bool $logging){
            $this->WriteAttributeBoolean("logging", $logging);
            $archID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];

            if($logging){
                AC_SetLoggingStatus($archID, $this->GetIDForIdent("current"), true);

                /** @noinspection PhpUndefinedFunctionInspection */
                AC_SetCompaction($archID, $this->GetIDForIdent("current"), 1, 1);
            }else{
                AC_SetLoggingStatus($archID, $this->GetIDForIdent("current"), false);
            }
        }

        public function loadHistoricDataToArchive(){
            $archID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];

            $level = $this->ReadAttributeString("levelAtt");
            $histData_URL = "https://pegelonline.wsv.de/webservices/rest-api/v2/stations/" . $level . "/W/measurements.json?start=P32D";
            $histData_json = file_get_contents($histData_URL);
            $histData = json_decode($histData_json, true);
            foreach ($histData as $hd){
                AC_AddLoggedValues($archID, $this->GetIDForIdent("current"), [['TimeStamp' => strtotime($hd['timestamp']), 'Value' => $hd['value']/100.0]]);
            }
            AC_ReAggregateVariable($archID, $this->GetIDForIdent("current"));
        }

        /**
         * @return void
         */
        public function UpdateCurrent(){
            $level = $this->ReadAttributeString("levelAtt");
            $current_URL = "https://pegelonline.wsv.de/webservices/rest-api/v2/stations/" . "$level" . "/W.json?includeCurrentMeasurement=true";
            $current_json = file_get_contents($current_URL);
            $currentData = json_decode($current_json, true);

            $oldCurrent = $this->GetValue("current");
            $newCurrent = $currentData["currentMeasurement"]["value"]/100.0;
            $this->SetValue("current", $newCurrent);
            $diffCurrent = $newCurrent - $oldCurrent;
            $this->SetValue("tendency", $diffCurrent/abs($diffCurrent));
            $this->SetValue("leveltimestamp", strtotime($currentData["currentMeasurement"]["timestamp"]));

        }
    }