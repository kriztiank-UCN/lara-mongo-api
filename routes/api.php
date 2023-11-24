<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// added to have access to Models\Post from within our API
use App\Models\CustomerMongoDB;
use App\Models\CustomerSQL;

use MongoDB\Laravel\Document;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
    Just a test
*/
Route::get('/hello_world/', function (Request $request) {
    return ['msg' => 'hello_world'];
});

/*
   Send a ping to our MongoDB cluster to see if our connection settings are correct
*/
Route::get('/test_mongodb', function (Request $request) {

    $connection = DB::connection('mongodb');
    $msg = 'MongoDB is accessible!';
    try {
        $connection->command(['ping' => 1]);
    } catch (\Exception $e) {
        $msg =  'MongoDB is not accessible. Error: ' . $e->getMessage();
    }

    return ['msg' => $msg];
});

/*
    Laravel check on the MySQL connection
*/
Route::get('/test_mysql/', function (Request $request) {
    try {
        DB::connection()->getPdo();
        return ['status' => 'executed', 'data' => 'Successfully connected to the DB.'];
    } catch (\Exception $e) {
        return ['status' => 'FAIL. exception', 'data' => $e];
    }
});

/*
    Create a new "customer" in our SQL database
    This is just to show the code looks identical to the MongoDB version
*/
Route::get('/create_eloquent_sql/', function (Request $request) {

    try {
        $success = CustomerSQL::create([
            'guid'        => 'cust_0000',
            'first_name'  => 'John',
            'family_name' => 'Doe',
            'email'       => 'j.doe@gmail.com',
            'address'     => '123 my street, my city, zip, state, country'
        ]);
        $msg = "OK";
    } catch (\Exception $e) {
        $msg =  'Create user via Eloquent SQL model. Error: ' . $e->getMessage();
    }

    return ['status' => 'executed', 'msg' => $msg];
});

/*
    Create a new "customer" in our MongoDB database
    The code looks identical to the SQL version
    run this route to create a new customer in the MongoDB database:
    http://lara-mongo-api.test/api/create_eloquent_mongo
*/
Route::get('/create_eloquent_mongo/', function (Request $request) {
    try {
        $success = CustomerMongoDB::create([
            'guid'        => 'cust_1111',
            'first_name'  => 'John',
            'family_name' => 'Doe',
            'email'       => 'j.doe@gmail.com',
            'address'     => '123 my street, my city, zip, state, country'
        ]);
        $msg = "OK";
    } catch (\Exception $e) {
        $msg =  'Create user via Eloquent MongoDB model. Error: ' . $e->getMessage();
    }

    return ['status' => 'executed', 'data' => $msg];
});

/*
    Find a record using Eloquent + MongoDB
    http://lara-mongo-api.test/api/find_eloquent
*/
Route::get('/find_eloquent/', function (Request $request) {

    $customer = CustomerMongoDB::where('guid', 'cust_1111')->get();

    return ['status' => 'executed', 'data' => $customer];
});

/*
    Update a record using Eloquent + MongoDB
    http://lara-mongo-api.test/api/update_eloquent
*/
Route::get('/update_eloquent/', function (Request $request) {
    $result = CustomerMongoDB::where('guid', 'cust_1111')->update(['first_name' => 'Jimmy']);

    return ['status' => 'executed', 'data' => $result];
});

/*
   Delete a record using Eloquent + MongoDB
   http://lara-mongo-api.test/api/delete_eloquent
*/
Route::get('/delete_eloquent/', function (Request $request) {
    $result = CustomerMongoDB::where('guid', 'cust_1111')->delete();

    return ['status' => 'executed', 'data' => $result];
});
