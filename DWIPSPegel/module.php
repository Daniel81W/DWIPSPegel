<?php

declare(strict_types=1);
	class DWIPSPegel extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();
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

            $form = "";
            $form .= "\"elements\": [";
            $form .= "{\"type\": \"Select\",\"name\":\"water\",\"caption\":\"Gewässer\",\"options\":[";
                foreach ($waters as $water){
                    if($i>0){$form .= ",";}
                    $form .= "{\"caption\": \"" . $i+1 . "\",\"value\":\"" . $i+1 . "\"}";
                    $i++;
                }
            $form .= "]}";
            $form .= "],";

            $form .= "\"actions\": [],";

            $form .= "\"status\": []";
            return $form;
        }
    }