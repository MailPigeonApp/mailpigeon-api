<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/index.php';

use Leaf\Helpers\Authentication;

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
						"message" => "Invalid API key"
					], 401
				);
			}

			$fields = db()
				->select('project', 'fields, deleted_count')
				->find($keyDetails["projectId"]);

			$decodedFields = json_decode($fields['fields'], true);

			$count = count($decodedFields);

			if ($count < 1) {
				response()->exit(
					[
						"message" => "No fields found for this project"
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
				if (gettype($request[$field['name']]) != $field['type']) {
					$empty[] = "Field '" . $field["name"] . "' is not of type " . $field['type'];
				}
			}

			if(count($empty) > 0) {
				response()->exit(
					$empty, 400
				);
			};

			if(count($required) > 0) {
				response()->exit(
						$required, 400
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
					

			response()->json(
				[
					"message" => "Submission successful"
				], 201, true
			);
	});
});
});

app()->run();
