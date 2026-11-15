<?php
namespace BuiltMighty\IMETF;

// Import the necessary Google API classes.
use Google_Client;
use Google_Service_Sheets;

class ImetfGoogleSheets {

    private $jsonKeyPath;

    public function __construct($jsonKeyPath) {
        $this->jsonKeyPath = $jsonKeyPath;
    }

    public function readSheet($spreadsheetId, $range) {
        try {
            // Create and authenticate the Google Sheets API client
            $client = new Google_Client();
            $client->setAuthConfig($this->jsonKeyPath);
            $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);
            $service = new Google_Service_Sheets($client);
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            return $response->getValues(); // Return the sheet data as an array
        } catch (\Exception $e) {
            throw new \Exception('Google Sheets API error: ' . $e->getMessage());
        }
    }
}
