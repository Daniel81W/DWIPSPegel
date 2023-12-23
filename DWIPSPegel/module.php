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
                $this->MaintainVariable("current", "Aktueller Wert", 2, "~ValueLength.KNX", 1, false);
            }else{
                $this->MaintainVariable("current", "Aktueller Wert", 2, "~ValueLength.KNX", 1, true);
                $this->MaintainVariable("lat", "Breitengrad", 2, "", 10, true);
                $this->MaintainVariable("long", "Längengrad", 2, "", 11, true);



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
                $this->SetValue("current", $wseries["currentMeasurement"]["value"]/$unitDiv);
                $this->SetValue("lat", $levelData["latitude"]);
                $this->SetValue("long", $levelData["longitude"]);
            }
        }

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
           // AC_AddLoggedValues($archID, $this->GetIDForIdent("current"), [['TimeStamp' => strtotime($histData[0]['timestamp']), 'Value' => 4.2]]);//$histData[0]['value']/100.0]);
        }
    }