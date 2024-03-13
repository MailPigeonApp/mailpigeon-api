<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/index.php';

use Leaf\Helpers\Authentication;
use Leaf\Fetch;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;



app()->cors();


app()->get('/', function () {
	response()->page('./welcome.html');
});

app()->set404(function () {
	response()->page('./404.html');
});

app()->group('/api', function(){
	app()->group('/v1', function(){
		app()->post('/submit', function(){
			$request = request()->body();
			$bearer = Leaf\Http\Headers::get("Authorization");
			$key = substr($bearer, 7);
			// $auth = Leaf\Http\Headers::get("Authorization");

			// $key = substr($auth, 6);

			// $decoded = base64_decode($key);
			// list($username,$password) = explode(":",$decoded);

			$keyDetails = db()
				->select('apikey')
				->find($key);

			if (!$keyDetails) {
				response()->exit(
					[
						"data" => [
							"message" => "Submission failed",
							"error" => [
								"message" => "Invalid API key"
								]
							],
						"status" => [
							"code" => 401,
							"message" => "Unauthorized"
						]
					], 401
				);
			}

			$fields = db()
				->select('project', 'name, fields, deleted_count, active_integrations')
				->find($keyDetails["projectId"]);

			$decodedFields = json_decode($fields['fields'], true);

			$count = count($decodedFields);

			if ($count < 1) {
				response()->exit(
					[
						"data" => [
							"message" => "Submission failed",
							"error" => [
								"message" => "No fields found for this project"
								]
							],
						"status" => [
							"code" => 406,
							"message" => "Unauthorized"
						]
					], 406
				);
			};

			$decodedFields = json_decode($fields['fields'], true);

			$required = [];
			$empty = [];

			foreach ($decodedFields as $field) {
				if ($field['required'] && !isset($request[$field["name"]])) {
					// $fieldName = $field["name"];
					$required[] = "Field '" . $field["name"] . "' is required";
					continue;
				}

				if ($field['type'] == "array" && substr(json_encode($request[$field['name']]), 0, 1) != "[") {
					$empty[] = "Field '" . $field["name"] . "' is not of type " . $field['type'];
				}

				if (gettype($request[$field['name']]) != $field['type'] && $field['type'] != "array" && $field['type'] != "file") {
					$empty[] = "Field '" . $field["name"] . "' is not of type " . $field['type'];
				}
			}

			if(count($empty) > 0) {
				response()->exit(
					[
						"data" => [
							"message" => "Submission failed",
							"error" => $empty
							],
						"status" => [
							"code" => 400,
							"message" => "Unauthorized"
						]
					], 400
				);
			};

			if(count($required) > 0) {
				response()->exit(
					[
						"data" => [
							"message" => "Submission failed",
							"error" => $required
							],
						"status" => [
							"code" => 400,
							"message" => "Unauthorized"
						]
					], 400
				);
			};

			$projectCount = db()
				->select('submission')
				->where([
					'"projectId"' => $keyDetails["projectId"]
					])
				->count();

			db()
				->insert('submission')
				->params(
					[
						'increment' => $projectCount + $fields['deleted_count'] + 1,
						'"projectId"' => $keyDetails["projectId"],
						'"userId"' => $keyDetails["userId"],
						"data" => json_encode($request)
					]
				)
				->execute();

			$activeIntegrations = $fields['active_integrations'];
			$activeIntegrations = trim($activeIntegrations, "{}");
			$activeIntegrationsArray = explode(",", $activeIntegrations);

			if (count($activeIntegrationsArray) === 0 || $activeIntegrationsArray[0] === "") {
				response()->json(
					[
						"message" => "Submission successful"
					], 201, true
				);
			} else {
				if (in_array("telegram", $activeIntegrationsArray)) {
					$telegramIntegration = db()
						->select("integrations", "data")
						->where([
							'"projectId"' => $keyDetails["projectId"],
							'type' => "telegram"
						])
						->first();

				

					$url = "https://telegram-worker.fly.dev:9091/send-message";
					$integrationData = json_decode($telegramIntegration['data'], true);

					//The data you want to send via POST
					$postFields = [
						'message' => "New submission on **" . $fields['name']. "**",
						'chatId' => $integrationData['chatId'],
					];
					
					//url-ify the data for the POST
					$fields_string = http_build_query($postFields);
					
					//open connection
					$ch = curl_init();
					
					//set the url, number of POST vars, POST data
					curl_setopt($ch,CURLOPT_URL, $url);
					curl_setopt($ch,CURLOPT_POST, true);
					curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
					
					//So that curl_exec returns the contents of the cURL; rather than echoing it
					curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
					
					//execute post
					$result = curl_exec($ch);

					response()->json(
						[
							"message" => "Submission successful",
							"integration" => $result
						], 201, true
					);

				};
			};
	
	
		});

		app()->post('/upload', function(){
			$request = request()->get(['file']);
			$bearer = Leaf\Http\Headers::get("Authorization");
			$key = substr($bearer, 7);

			if (!$key) {
				response()->exit(
					[
						"data" => [
							"message" => "Upload failed",
							"error" => [
								"message" => "Invalid API key"
								]
							],
						"status" => [
							"code" => 401,
							"message" => "Unauthorized"
						]
					], 401
				);
			}

			// get project fields from db

			$keyDetails = db()
				->select('apikey')
				->find($key);

			if (!$keyDetails) {
				response()->exit(
					[
						"data" => [
							"message" => "Upload failed",
							"error" => [
								"message" => "Project not found"
								]
							],
						"status" => [
							"code" => 404,
							"message" => "Not Found"
						]
					], 404
				);
			}

			$fields = db()
				->select('project', 'fields')
				->find($keyDetails["projectId"]);

			$decodedFields = json_decode($fields['fields'], true);

			// check if any fields' type is file
			$fileField = false;

			foreach ($decodedFields as $field) {
				if ($field['type'] == "file") {
					$fileField = true;
					break;
				}
			}

			if (!$fileField) {
				response()->exit(
					[
						"data" => [
							"message" => "Upload failed",
							"error" => [
								"message" => "No file field found for this project"
								]
							],
						"status" => [
							"code" => 406,
							"message" => "Not Acceptable"
						]
					], 406
				);
			}
			
			// TODO: create lock for file upload to prevent multiple uploads and 
			// associate file type with submission
			$sdk = new Aws\Sdk([
				'region'   => 'us-east-1',
				'version'  => 'latest',
				'credentials' => [
					'key'    => $_SERVER['AWS_ACCESS_KEY_ID'],
					'secret' => $_SERVER['AWS_SECRET'],
				]
			]);
		
			$s3Client = $sdk->createS3();
		
			$result = $s3Client->putObject([
				'Bucket' => $_SERVER['AWS_S3_BUCKET'],
				'ACL'  => 'public-read',
				'Key' => $request['file']['name'], 
				'Body' => fopen($request['file']['tmp_name'], 'rb'),
			]);
		
			$url = $result->get('ObjectURL');
		
			if(!$url){
				response()->exit($result, 500, false);
			}
		
			response()->json([
				'url' => $result->get('ObjectURL')
			]);
		});

		app()->post('/submitWithFile', function(){
			response()->json("Not implemented", 501);
		});
	});
});

app()->run();
