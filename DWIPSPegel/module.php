<?php

declare(strict_types=1);
    /** @noinspection PhpUnused */
	class DWIPSPegel extends IPSModule
	{
        /** @noinspection PhpExpressionResultUnusedInspection */
        public function Create()
		{
			//Never delete this line!
			parent::Create();

            //$this->RegisterPropertyString("water", "");
            $this->RegisterAttributeString("waterAtt", "");
            $this->RegisterAttributeString("levelAtt", "");
            $this->RegisterPropertyBoolean("archive", true);
		}

        /** @noinspection PhpRedundantMethodOverrideInspection */
		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

        /** @noinspection PhpRedundantMethodOverrideInspection */
        public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}

        public function GetConfigurationForm()
        {
            $selectedWater = $this->ReadAttributeString("waterAtt");
            $selectedLevel= $this->ReadAttributeString("levelAtt");
            $jsonForm = json_decode(file_get_contents(__DIR__ . "/form.json"), true);

            $waters_URL = "https://pegelonline.wsv.de/webservices/rest-api/v2/waters.json";
            $waters_json = file_get_contents($waters_URL);
            $waters = json_decode($waters_json);

            $waterOptions = array();
            $waterArray = array("caption" => "", "value" => "");
            $waterOptions[] = $waterArray;
            foreach ($waters as $water){
                $waterArray = array("caption" => $water->longname, "value" => $water->longname);
                $waterOptions[] = $waterArray;
            }
            $jsonForm["elements"][0]["options"] = $waterOptions;
            $jsonForm["elements"][0]["value"] = $selectedWater;

            $waterLevels_URL = "https://pegelonline.wsv.de/webservices/rest-api/v2/stations.json";
            if($this->ReadAttributeString("waterAtt") <> ""){
                $waterLevels_URL .= "?waters=" . $selectedWater;
            }

            $waterLevels_json = file_get_contents($waterLevels_URL);
            $levels = json_decode($waterLevels_json);
            $levelOptions = array();
            $levelArray = array("caption" => "", "value" => "");
            $levelOptions[] = $levelArray;
            foreach ($levels as $level){
                $levelArray = array("caption" => $level->longname, "value" => $level->uuid);
                $levelOptions[] = $levelArray;
            }
            $jsonForm["elements"][1]["options"] = $levelOptions;
            $jsonForm["elements"][1]["value"] = $selectedLevel;

            if($selectedLevel <> ""){
                $jsonForm["elements"][2]["visible"] = true;
            }




            return json_encode($jsonForm);
        }

        /** @noinspection PhpUnused */
        public function ReloadConfigurationForm(){
            /** @noinspection PhpExpressionResultUnusedInspection */
            $this->ReloadForm();
        }

        /** @noinspection PhpUnused */
        public function WriteAttributeWaterAtt(string $val){
            /** @noinspection PhpExpressionResultUnusedInspection */
            $this->WriteAttributeString("waterAtt", $val);
        }

        /** @noinspection PhpUnused */
        public function changeLevel(string $level){
            /** @noinspection PhpExpressionResultUnusedInspection */
            $this->WriteAttributeString("levelAtt", $level);

            if($level == ""){
                $this->MaintainVariable("current", "Aktueller Wert", 2, "~ValueLength.KNX", 1, false);
            }else{
                $this->MaintainVariable("current", "Aktueller Wert", 2, "~ValueLength.KNX", 1, true);
                $this->MaintainVariable("lat", "Breitengrad", 2, "", 10, true);
                $this->MaintainVariable("long", "Längengrad", 2, "", 11, true);



                $level_URL = "https://pegelonline.wsv.de/webservices/rest-api/v2/stations/" . "$level" . ".json?includeTimeseries=true&includeCurrentMeasurement=true&includeCharacteristicValues=true";
                $level_json = file_get_contents($level_URL);
                $levelData = json_decode($level_json);
                $this->SetValue("lat", $levelData->latitude);
                $this->SetValue("long", $levelData->longitude);
            }
        }
    }