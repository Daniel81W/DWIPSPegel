<?php

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
            $waters_URL = "https://pegelonline.wsv.de/webservices/rest-api/v2/waters.json";
            $waters_json = file_get_contents($waters_URL);
            $waters = json_decode($waters_json);

            $i = 0;

            $jsonForm = json_decode(file_get_contents(__DIR__ . "/form.json"), true);
            $waterOptions = array();
            foreach ($waters as $water){
                $waterArray = array("caption" => $water->longname, "value" => $water->longname);
                $waterOptions[] = $waterArray;
            }
            $jsonForm["elements"][0]["options"] = $waterOptions;
            $jsonForm["elements"][0]["value"] = $this->ReadAttributeString("waterAtt");

            $waterLevels_URL = "https://pegelonline.wsv.de/webservices/rest-api/v2/stations.json";
            if($this->ReadAttributeString("waterAtt") != ""){
                $waterLevels_URL .= "?waters=" . $this->ReadAttributeString("waterAtt");
            }
            $waterLevels_json = file_get_contents($waterLevels_URL);
            $levels = json_decode($waterLevels_json);
            $levelOptions = array();
            foreach ($levels as $level){
                $levelArray = array("caption" => $level->longname, "value" => $water->uuid);
                $levelOptions[] = $levelArray;
            }
            $jsonForm["elements"][1]["options"] = $levelOptions;
            $jsonForm["elements"][1]["value"] = $this->ReadAttributeString("levelAtt");






            return json_encode($jsonForm);
        }

        public function ReloadConfigurationForm(){
            $this->ReloadForm();
        }

        public function WriteAttributeWaterAtt(string $val){
            $this->WriteAttributeString("waterAtt", $val);
        }

        public function WriteAttributeLevelAtt(string $val){
            $this->WriteAttributeString("levelAtt", $val);
        }
    }