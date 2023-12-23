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
            $jsonForm["elements"][0]["value"] = $this->ReadAttributeString("waterAtt");

            $waterLevels_URL = "https://pegelonline.wsv.de/webservices/rest-api/v2/stations.json";
            if($this->ReadAttributeString("waterAtt") <> ""){
                $waterLevels_URL .= "?waters=" . $this->ReadAttributeString("waterAtt");
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
            $jsonForm["elements"][1]["value"] = $this->ReadAttributeString("levelAtt");






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
        public function WriteAttributeLevelAtt(string $val){
            /** @noinspection PhpExpressionResultUnusedInspection */
            $this->WriteAttributeString("levelAtt", $val);
        }
    }