<?php
require __DIR__ . '/vendor/autoload.php';

class Sheets{     

    function getData(){
        $server = 'kendali-irigasi.com';     // change if necessary
        $port = 1883;                     // change if necessary
        $username = 'mni';                   // set your username
        $password = 'TTu71@k1eQ';                   // set your password
        $client_id = ''; // make sure this is unique for connecting to sever - you could use uniqid()
        $mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);
        if(!$mqtt->connect(true, NULL, $username, $password)) {
            echo " mqtt error ";
		
        }
        
        $data = $mqtt->subscribeAndWaitForMessage('sheets/bbopt', 0);
	
        $mqtt->close();
        return $data;

    }

    function storeData(){
        $year = date("Y");
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig('credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $service = new Google_Service_Sheets($client);
        $spreadsheetId = '1sgsmZn8Uq_CaSfzMqrKs2W4ZfL7LD-D0lQcSoTpcZkk';
        $range = $year;
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        $data = $this->getData();
        
        if($data== ""){
            echo "Data MQTT Kosong ";
            die;
        }

        $dataSplit = explode(",", $data);
        $dateTime = $dataSplit[0];
        $dateTimeSplit = explode(" ", $dateTime);
        $date = $dateTimeSplit[0];
        $time = $dateTimeSplit[1];       
        $curahHujan = $dataSplit[1];
        // // $soil_mosture = $data['soil_mosture'];
        
        //Insert Data
        $values = [
            [
                $date,$time,$curahHujan
            ],
        ];
        
        $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);
        $params = [
            'valueInputOption' => 'RAW'
        ];

        $insert = [
            'insertDataOption' => 'INSERT_ROW'
        ];

        $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
        


        if ($result) {
		    $storedata = new Sheets();		
        } else {
            echo "Data Gagal Disimpan";
	        printf("%d cells appended.", $result->getUpdates()->getUpdatedCells());
        } 
    }   


    
}

while(true){
    $storedata = new Sheets();
    $storedata->storeData();
}