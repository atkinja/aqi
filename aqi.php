<?php

//  Step 1:  Update your API key for Airnow. 
//  By default you only have one API key, but this tool supports multiple API keys if needed to handle greater quantity of requests.
//  You can obtain an Airnow API key by going to https://docs.airnowapi.org/account/request/

$apiKey = array("enter-your-key");

// Example if you have multiple keys
// $apiKey = array("enter-your-key", "enter-your-key");


// Step 2:  Update your API key for Google
// Google API key to create the shortened URL
$googleApiKey = "enter-google-api-key";


//////////////////// Nothing to modify below here /////////

// This variable is used to support multiple API keys
$arrayCount = count($apiKey) - 1;

// Array for the descriptions of each AQI levels
$aqiLevels = array(
	"Good" => "AQI is 0 to 50. Air quality is considered satisfactory, and air pollution poses little or no risk.",
	"Moderate" => "AQI is 51 to 100. Air quality is acceptable; however, for some pollutants there may be a moderate health concern for a very small number of people. For example, people who are unusually sensitive to ozone may experience respiratory symptoms.",
	"Unhealthy for Sensitive Groups" => "AQI is 101 to 150. Although general public is not likely to be affected at this AQI range, people with lung disease, older adults and children are at a greater risk from exposure to ozone, whereas persons with heart and lung disease, older adults and children are at greater risk from the presence of particles in the air.",
	"Unhealthy" => "AQI is 151 to 200. Everyone may begin to experience some adverse health effects, and members of the sensitive groups may experience more serious effects.",
	"Very Unhealthy" => "AQI is 201 to 300. This would trigger a health alert signifying that everyone may experience more serious health effects.",
	"Hazardous" => "AQI greater than 300. This would trigger a health warnings of emergency conditions. The entire population is more likely to be affected."
    );




// Pull the zipcode from the text message
$zipcode = $_REQUEST['Body'];
$zipcode = preg_replace('/\s+/', '', $zipcode);
if (strlen($zipcode) != 5)  {
	$response = "Sorry, your zip code is not 5 digits long.  Please retry with a 5 digit zipcode.  Thank you.";	
} elseif (is_numeric($zipcode))  {

		$randNum =  rand(0, $arrayCount);
		$url = "http://www.airnowapi.org/aq/observation/zipCode/current/?format=application/json&zipCode=$zipcode&distance=50&API_KEY=$apiKey[$randNum]";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$output = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		$output_o=json_decode($output,true);

		$count =  count($output_o);



		for( $i = 0; $i<$count; $i++ ) {
			if (strcmp($output_o[$i]['ParameterName'],"PM2.5") == 0) {
				$statusPM25 =  $output_o[$i]['Category']['Name'];
				$aqiPM25 = $output_o[$i]['AQI'];
				$datePM25 = $output_o[$i]['DateForecast'];
				$areaPM25 =  $output_o[$i]['ReportingArea'];
				$hourPM25 =  $output_o[$i]['HourObserved'];
				$tzPM25 =  $output_o[$i]['LocalTimeZone'];

			}
			
		}

                for( $j = 0; $j<$count; $j++ ) {
                        if (strcmp($output_o[$j]['ParameterName'],"O3") == 0) {
                                $statusO3 =  $output_o[$j]['Category']['Name'];
                                $aqiO3 = $output_o[$j]['AQI'];
                                $dateO3 = $output_o[$j]['DateForecast'];
                                $areaO3 =  $output_o[$j]['ReportingArea'];
                                $hourO3 =  $output_o[$j]['HourObserved'];
                                $tzO3 =  $output_o[$j]['LocalTimeZone'];

                        }

                }


		if ($aqiPM25 >= $aqiO3) {
   			        $status = $statusPM25;
                                $aqi = $aqiPM25;
                                $date = $datePM25;
                                $area = $areaPM25;
                                $hour = $hourPM25;
                                $tz = $tzPM25;
		} else {
				$status = $statusO3;
                                $aqi = $aqiO3;
                                $date = $dateO3;
                                $area = $areaO3;
                                $hour = $hourO3;
                                $tz = $tzO3;

		}



		foreach ($aqiLevels as $aqiStatus => $description) {
			if (strcmp($aqiStatus,$status) == 0) {
				$desc = $description;
			}
		}

                $longUrl = "https://airnow.gov/index.cfm?action=airnow.local_city&zipcode=$zipcode&submit=Go";
                $url = "https://www.googleapis.com/urlshortener/v1/url?key=$googleApiKey";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                ));
                $payload = array('longUrl' => $longUrl);
                $data_json = json_encode($payload);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
                $output = curl_exec($ch);
                $info = curl_getinfo($ch);
                curl_close($ch);
                $output_o=json_decode($output,true);
		$shortenedUrl = str_replace("https://","",$output_o['id']);

		if ($hour > 11) {
			$amPm = "PM";	
			if ($hour > 12) {
				$hour = $hour - 12;
			}
		} else {
			$amPm = "AM";
		}

		if ($status) {
			$response = "$status for $area at $hour$amPm $tz. AQI = $aqi. $desc See $shortenedUrl for more details.";
		} else {
			$response = "Zip Code $zipcode does not currently have air quality data available from the airnow.gov API. See the EPA website: $shortenedUrl for more details";
		
		}


} else {
	$response = "Sorry, your zip code is not numeric.  Please retry with a 5 digit numeric zipcode.  Thank you.";	
}

?>

<Response>
<Message>
     <?php echo $response ?>
</Message>
</Response>
