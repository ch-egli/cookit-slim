<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Factory\StreamFactory;

/**
 * GET recipes
 */
$app->get('/api/recipes', function( Request $request, Response $response){
    $headerValueArray = $request->getHeader('Authorization');
    $authResult = Authentication::authenticate($headerValueArray);
    if (!empty($authResult)) {
        return JsonResponse::withJson($response, json_encode((object) ['error' => $authResult]), 401);
    }

    $sql = "SELECT id, title, description, tags, created_at, updated_at FROM recipes";
    try {
        $db = new Database();
        $db = $db->connect();

        $stmt = $db->query( $sql );
        $recipes = $stmt->fetchAll( PDO::FETCH_OBJ );
        $db = null; // clear db object

        return JsonResponse::withJson($response, json_encode($recipes), 200);
    } catch( PDOException $e ) {
        $errorMsg = (object) ['error' => $e->getMessage()];
        return JsonResponse::withJson($response, json_encode($errorMsg), 500);
    }
});

/**
 * GET recipe
 */
$app->get('/api/recipes/{id}', function( Request $request, Response $response){
    $headerValueArray = $request->getHeader('Authorization');
    $authResult = Authentication::authenticate($headerValueArray);
    if (!empty($authResult)) {
        return JsonResponse::withJson($response, json_encode((object) ['error' => $authResult]), 401);
    }

    $recipeId = $request->getAttribute('id');
    $sql = "SELECT id, title, description, tags, image1, image2, image3, created_at, updated_at FROM recipes WHERE id=".$recipeId;

    try {
        $db = new Database();
        $db = $db->connect();

        $stmt = $db->query( $sql );
        $recipes = $stmt->fetchAll( PDO::FETCH_OBJ );
        $db = null; // clear db object

        $countRecipes = count($recipes);
        if ($countRecipes < 1) {
            $errorMsg = (object) ['error' => 'recipe with id ' . $recipeId . ' not found'];
            return JsonResponse::withJson($response, json_encode($errorMsg), 404);
        } else if ($countRecipes > 1) {
            $errorMsg = (object) ['error' => 'Found multiple recipes with id ' . $recipeId];
            return JsonResponse::withJson($response, json_encode($errorMsg), 400);
        } else  {
            $rp = array_values($recipes)[0];
            $recipe = (object) [
                'id' => $rp->id,
                'title' => $rp->title,
                'description' => $rp->description,
                'tags' => $rp->tags,
                'image1' => $rp->image1 == null ? null : 'available',
                'image2' => $rp->image2 == null ? null : 'available',
                'image3' => $rp->image3 == null ? null : 'available',
                'created_at' => $rp->created_at,
                'updated_at' => $rp->created_at,
            ];
            // var_dump($recipe);
            return JsonResponse::withJson($response, json_encode($recipe), 200);
        }
    } catch( PDOException $e ) {
        $errorMsg = (object) ['error' => $e->getMessage()];
        return JsonResponse::withJson($response, json_encode($errorMsg), 500);
    }
});

/**
 * Add new recipe
 */
$app->post('/api/recipes', function( Request $request, Response $response){
    $headerValueArray = $request->getHeader('Authorization');
    $authResult = Authentication::authenticate($headerValueArray);
    if (!empty($authResult)) {
        return JsonResponse::withJson($response, json_encode((object) ['error' => $authResult]), 401);
    }

    $array = $request->getParsedBody();
    $parsedBody = implode(',', $array);
    // echo "parsedBody: $parsedBody \n";

    $uploadedFiles = $request->getUploadedFiles();
    $fileCount = count($uploadedFiles);
    //echo "fileCount: $fileCount";

    $filename1 = null;
    $stream1 = null;
    $filename2 = null;
    $stream2 = null;
    $filename3 = null;
    $stream3 = null;

    if ($fileCount > 0) {
        $uploadedFile1 = array_values($uploadedFiles)[0];
        $filename1 = $uploadedFile1->getClientFilename();
        $stream1 = $uploadedFile1->getStream();
    }
    if ($fileCount > 1) {
        $uploadedFile2 = array_values($uploadedFiles)[1];
        $filename2 = $uploadedFile2->getClientFilename();
        $stream2 = $uploadedFile2->getStream();
    }
    if ($fileCount > 2) {
        $uploadedFile3 = array_values($uploadedFiles)[2];
        $filename3 = $uploadedFile3->getClientFilename();
        $stream3 = $uploadedFile3->getStream();
    }

    // echo "uploadedFiles: $filename1 $filename2 $filename3 ";
    // echo "$stream: $stream";

    $title = $array['title'];
    $description = $array['description'];
    $tags = $array['tags'];

    $sql = "INSERT INTO recipes (title, description, tags, image1, image2, image3, created_at, updated_at) 
            VALUES(:title, :description, :tags, :image1, :image2, :image3, NOW(), NOW())";

    // echo "sql: $sql \n";

    try {
        $db = new Database();
        $db = $db->connect();
        $stmt = $db->prepare( $sql );

        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':tags', $tags);
        $stmt->bindParam(':image1', $stream1);
        $stmt->bindParam(':image2', $stream2);
        $stmt->bindParam(':image3', $stream3);

        $stmt->execute();

        $successMsg = (object) ['success' => "new recipe added"];
        return JsonResponse::withJson($response, json_encode($successMsg), 201);
    } catch( PDOException $e ) {
        $errorMsg = (object) ['error' => $e->getMessage()];
        return JsonResponse::withJson($response, json_encode($errorMsg), 500);
    }
});

/**
 * GET image of recipe:
 * Params: image is one of 1, 2 or 3
 * Attention: does not work with log output!
 */
$app->get('/api/recipes/{id}/img/{image}', function (Request $request, Response $response) {
    $headerValueArray = $request->getHeader('Authorization');
    $authResult = Authentication::authenticate($headerValueArray);
    if (!empty($authResult)) {
        return JsonResponse::withJson($response, json_encode((object) ['error' => $authResult]), 401);
    }

    $imgId = $request->getAttribute('image');
    $img = 'image' . $imgId;
    $recipeId = $request->getAttribute('id');
    $sql = "SELECT ".$img." FROM recipes WHERE id=".$recipeId;
    $image = null;
    try {
        $db = new Database();
        $db = $db->connect();
        $stmt = $db->query( $sql );
        $images = $stmt->fetchAll( PDO::FETCH_OBJ );
        $db = null; // clear db object

        // $imgCount = count($images);
        // echo "imgCount: $imgCount";
        // var_dump($images);

        $countImages = count($images);
        if ($countImages < 1) {
            $errorMsg = (object) ['error' => 'image ' . $imgId . ' of recipe with id ' . $recipeId . ' not found'];
            return JsonResponse::withJson($response, json_encode($errorMsg), 404);
        } else {
            // rawImage is of type "stdClass" with property names that correspond to the column names
            $rawImage = array_values($images)[0];
            $image = $rawImage->$img;
            if ($image == null) {
                $errorMsg = (object) ['error' => 'image ' . $imgId . ' of recipe with id ' . $recipeId . ' not found'];
                return JsonResponse::withJson($response, json_encode($errorMsg), 404);
            }
        }
    } catch( PDOException $e ) {
        $errorMsg = (object) ['error' => 'invalid request: ' . $e->getMessage()];
        return JsonResponse::withJson($response, json_encode($errorMsg), 400);
    }

    $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $image);
    $response = $response->withStatus(200)->withHeader('Content-Type', $mime);
    return $response->withBody((new StreamFactory())->createStream($image));
});