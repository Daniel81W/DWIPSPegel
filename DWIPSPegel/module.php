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
            return json_encode($jsonForm);
            /*
            $form = "";
            $form .= "{\"elements\": [";
            $form .= "{\"type\": \"Select\",\"name\": \"water\",\"caption\": \"Gewässer\",\"options\": [";
                foreach ($waters as $water){
                    if($i>0){$form .= ",";}
                    $form .= "{\"caption\": \"" . $water->longname . "\", \"value\": " . $i+1 . "}";
                    $i++;
                }
            $form .= "]}";
            $form .= "],";

            $form .= "\"actions\": [],";

            $form .= "\"status\": []}";
            return $form;*/
        }

        public function ReloadConfigurationForm(){
            $this->ReloadForm();
        }

        public function WriteAttributeWaterAtt(string $val){
            $this->WriteAttributeString("waterAtt", $val);
            $this->SendDebug("Form", $val, 0);
        }
    }