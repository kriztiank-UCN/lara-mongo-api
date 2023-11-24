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
  MANDATORY ASSIGNMENT
 Create a new "Students collection" in our MongoDB database
*/

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

/*
    Create a new record with nested data, using Eloquent
*/
Route::get('/create_nested/', function (Request $request) {
    $message = "executed";
    $success = null;
    // replace address string with an object (PHP data structure)
    $address = new stdClass;
    $address->street = '123 my street name';
    $address->city   = 'my city';
    $address->zip    = '12345';
    // replace one email string with an array of email strings (PHP data structure)
    $emails = ['j.doe@gmail.com', 'j.doe@work.com'];
    // assign data structures to the object we want to save in the database
    try {
        $customer = new CustomerMongoDB();
        $customer->guid         = 'cust_2222';
        $customer->first_name   = 'John';
        $customer->family_name  = 'Doe';
        $customer->email        = $emails; // array of email strings
        $customer->address      = $address; // object with street, city, zip
        $success = $customer->save();       // save() returns 1 or 0
    } catch (\Exception $e) {
        $message = $e->getMessage();
    }

    return ['status' => $message, 'data' => $success];
});

/*
    Find records using a native MongoDB Query
    1 - with Model->whereRaw()
    2 - with native Collection->findOne()
    3 - with native Collection->find()
*/
Route::get('/find_native/', function (Request $request) {

    // a simple MongoDB query that looks for a customer based on the guid
    $mongodbquery = ['guid' => 'cust_2222'];

    // Option #1
    //========== whereraw
    // use Eloquent's whereRaw() function. This is the easiest way to stay close to the Laravel paradigm
    // returns an "Illuminate\Database\Eloquent\Collection" Object
    $results = CustomerMongoDB::whereRaw($mongodbquery)->get();

    // Option #2
    //========== document
    // use the native MongoDB driver Collection object. with it, you can use the native MongoDB Query API
    //
    $mdb_collection = DB::connection('mongodb')->getCollection('laracoll');

    // find the first document that matches the query
    $mdb_bsondoc    = $mdb_collection->findOne($mongodbquery); // returns a "MongoDB\Model\BSONDocument" Object

    // if we want to convert the MongoDB Document to a Laravel Model, use the Model's newFromBuilder() method
    $cust    = new CustomerMongoDB();
    $one_doc = $cust->newFromBuilder((array) $mdb_bsondoc);

    // Option #3
    //========== cursor_array
    // find all documents that matches the query
    // Note: we're using find without any arguments, so ALL documents will be returned
    $mdb_cursor       = $mdb_collection->find(); // returns a "MongoDB\Driver\Cursor" object
    $cust_array = array();
    foreach ($mdb_cursor->toArray() as $bson) {
        $cust_array[] = $cust->newFromBuilder($bson);
    }

    return ['status' => 'executed', 'whereraw' => $results, 'document' => $one_doc, 'cursor_array' => $cust_array];
});

/*
    Update a record using a native MongoDB Query
*/
Route::get('/update_native/', function (Request $request) {
    $mdb_collection = DB::connection('mongodb')->getCollection('laracoll');

    $match = ['guid' => 'cust_2222'];
    $update = ['$set' => ['first_name' => 'Henry', 'address.street' => '777 new street name']];
    $result = $mdb_collection->updateOne($match, $update);

    return ['status' => 'executed', 'matched_docs' => $result->getMatchedCount(), 'modified_docs' => $result->getModifiedCount()];
});

/*
    Find and delete the first record that matches the query
*/
Route::get('/delete_native/', function (Request $request) {
    $mdb_collection = DB::connection('mongodb')->getCollection('laracoll');

    $match = ['guid' => 'cust_2222'];
    $result = $mdb_collection->deleteOne($match);

    return ['status' => 'executed', 'deleted_docs' => $result->getDeletedCount()];
});

/*
    Executes an aggregation pipeline

    Compute the average ratings for every genre that we have in the database
*/
Route::get('/aggregate/', function (Request $request) {

    $mdb_collection = DB::connection('mongodb_mflix')->getCollection('movies');

    /* 
        The first stage is we're going to unwind the documents per genre,
        if a document has two genres like action and drama, 
        then we'll split those to make sure that we have only one genre per document
    */
    $stage0 = ['$unwind' => ['path' => '$genres']];
    // Group by genre and compute the average rating for every movie in that genre
    $stage1 = ['$group' => ['_id' => '$genres', 'averageGenreRating' => ['$avg' => '$imdb.rating']]];
    // Sort by higest rating first
    $stage2 = ['$sort' => ['averageGenreRating' => -1]];

    $aggregation = [$stage0, $stage1, $stage2];

    $mdb_cursor = $mdb_collection->aggregate($aggregation);

    return ['status' => 'executed', 'data' => $mdb_cursor->toArray()];
});
