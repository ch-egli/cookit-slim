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

    $queryParams = $request->getQueryParams();
    $titleFilterStr = assembleContainsQueryFor($queryParams, "title");
    $descrFilterStr = assembleContainsQueryFor($queryParams, "descr");
    $categoryFilterStr = assembleEqualsQueryFor($queryParams, "category");
    $effortFilterStr = assembleEqualsQueryFor($queryParams, "effort");
    $tagsFilterStr = assembleContainsQueryFor($queryParams, "tags");

    $sql = "SELECT r.id, r.title, r.description, r.category, r.effort, IFNULL(GROUP_CONCAT(t.name), '') AS tags, r.created_at, r.updated_at
            FROM recipes r
                LEFT JOIN tags t ON r.id = t.recipe_id
            WHERE r.title LIKE :titleFilter
              AND IFNULL(r.description, '') LIKE :descrFilter
              AND IFNULL(r.category, '') LIKE :categoryFilter
              AND IFNULL(r.effort, '') LIKE :effortFilter
              AND IFNULL(t.name, '') LIKE :tagsFilter
            GROUP BY r.id";

    try {
        $db = new Database();
        $db = $db->connect();

        $query = $db->prepare( $sql );
        $query->bindParam(':titleFilter', $titleFilterStr);
        $query->bindParam(':descrFilter', $descrFilterStr);
        $query->bindParam(':categoryFilter', $categoryFilterStr);
        $query->bindParam(':effortFilter', $effortFilterStr);
        $query->bindParam(':tagsFilter', $tagsFilterStr);
        $query->execute();
        $recipes = $query->fetchAll( PDO::FETCH_OBJ );
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
    $sql = "SELECT r.id, r.title, r.description, r.category, r.effort, IFNULL(GROUP_CONCAT(t.name), '') AS tags, image1, image2, image3, r.created_at, r.updated_at
            FROM recipes r
                LEFT JOIN tags t ON r.id = t.recipe_id
            WHERE r.id = :recipe_id
            GROUP BY r.id";

    try {
        $db = new Database();
        $db = $db->connect();

        $query = $db->prepare( $sql );
        $query->bindParam(':recipe_id', $recipeId);
        $query->execute();

        $recipes = $query->fetchAll( PDO::FETCH_OBJ );
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
                'category' => $rp->category,
                'effort' => $rp->effort,
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
 * Add or update a new recipe
 * Attention: we cannot use put since Content-Type "form-data" is not supported => getParsedBody always returns null!
 *
 * For requests use: Content-Type: multipart/form-data
 */
$app->post('/api/recipes', function( Request $request, Response $response){
    $headerValueArray = $request->getHeader('Authorization');
    $authResult = Authentication::authenticate($headerValueArray);
    if (!empty($authResult)) {
        return JsonResponse::withJson($response, json_encode((object) ['error' => $authResult]), 401);
    }

    $bodyParams = $request->getParsedBody();
    // $parsedBody = implode(',', $array);
    // echo "parsedBody: $parsedBody \n";

    $recipeId = $bodyParams['id'];
    $title = $bodyParams['title'];
    $description = $bodyParams['description'];
    $category = $bodyParams['category'];
    $effort = $bodyParams['effort'];
    $tags = $bodyParams['tags'];

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
    // echo "stream1: $stream1";

    $repo = new RecipeRepo();
    if (!empty($recipeId)) {
        $updateResult = $repo->update($recipeId, $title, $description, $category, $effort, $tags, $stream1, $stream2, $stream3);
        if (!empty($updateResult)) {
            return JsonResponse::withJson($response, json_encode((object) ['error' => $updateResult]), 400);
        }
        $successMsg = (object) ['success' => "recipe successfully updated"];
        return JsonResponse::withJson($response, json_encode($successMsg), 200);
    } else {
        $insertResult = $repo->insert($title, $description, $category, $effort, $tags, $stream1, $stream2, $stream3);
        if (!empty($insertResult)) {
            return JsonResponse::withJson($response, json_encode((object) ['error' => $insertResult]), 400);
        }
        $successMsg = (object) ['success' => "recipe successfully added"];
        return JsonResponse::withJson($response, json_encode($successMsg), 201);
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
    $sql = "SELECT ".$img." FROM recipes WHERE id = :recipe_id";
    $image = null;
    try {
        $db = new Database();
        $db = $db->connect();
        $query = $db->prepare( $sql );
        $query->bindParam(':recipe_id', $recipeId);
        $query->execute();

        $images = $query->fetchAll( PDO::FETCH_OBJ );
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

/**
 * DELETE recipe
 */
$app->delete('/api/recipes/{id}', function( Request $request, Response $response){
    $headerValueArray = $request->getHeader('Authorization');
    $authResult = Authentication::authenticate($headerValueArray);
    if (!empty($authResult)) {
        return JsonResponse::withJson($response, json_encode((object) ['error' => $authResult]), 401);
    }

    $recipeId = $request->getAttribute('id');
    $sql = "SELECT id, title FROM recipes WHERE id = :recipe_id";

    try {
        $db = new Database();
        $db = $db->connect();

        $query = $db->prepare( $sql );
        $query->bindParam(':recipe_id', $recipeId);
        $query->execute();
        $recipes = $query->fetchAll( PDO::FETCH_OBJ );

        $countRecipes = count($recipes);
        if ($countRecipes < 1) {
            $errorMsg = (object) ['error' => 'recipe with id ' . $recipeId . ' not found'];
            return JsonResponse::withJson($response, json_encode($errorMsg), 404);
        } else if ($countRecipes > 1) {
            $errorMsg = (object) ['error' => 'Found multiple recipes with id ' . $recipeId];
            return JsonResponse::withJson($response, json_encode($errorMsg), 400);
        } else  {
            $stmt = $db->prepare("DELETE FROM recipes WHERE id = $recipeId");
            $stmt->execute();
            $stmt = $db->prepare("DELETE FROM tags WHERE recipe_id = $recipeId");
            $stmt->execute();
            $db = null;
            $successMsg = (object) ['success' => "recipe successfully deleted"];
            return JsonResponse::withJson($response, json_encode($successMsg), 200);
        }
    } catch( PDOException $e ) {
        $errorMsg = (object) ['error' => $e->getMessage()];
        return JsonResponse::withJson($response, json_encode($errorMsg), 500);
    }
});

/**
 * GET tags
 */
$app->get('/api/tags', function( Request $request, Response $response){
    $headerValueArray = $request->getHeader('Authorization');
    $authResult = Authentication::authenticate($headerValueArray);
    if (!empty($authResult)) {
        return JsonResponse::withJson($response, json_encode((object) ['error' => $authResult]), 401);
    }

    $sql = "SELECT DISTINCT name FROM tags ORDER BY name ASC";
    return executeQuery($response, $sql, "name");
});

/**
 * GET categories
 */
$app->get('/api/categories', function( Request $request, Response $response){
    $headerValueArray = $request->getHeader('Authorization');
    $authResult = Authentication::authenticate($headerValueArray);
    if (!empty($authResult)) {
        return JsonResponse::withJson($response, json_encode((object) ['error' => $authResult]), 401);
    }

    $sql = "SELECT DISTINCT category FROM recipes ORDER BY category ASC";
    return executeQuery($response, $sql, "category");
});

/**
 * GET effort-values
 */
$app->get('/api/effort-values', function( Request $request, Response $response){
    $headerValueArray = $request->getHeader('Authorization');
    $authResult = Authentication::authenticate($headerValueArray);
    if (!empty($authResult)) {
        return JsonResponse::withJson($response, json_encode((object) ['error' => $authResult]), 401);
    }

    $sql = "SELECT DISTINCT effort FROM recipes ORDER BY effort ASC";
    return executeQuery($response, $sql, "effort");
});

/**
 * Handle bad routes
 */
$app->get('/[{path:.*}]', function  (Request $request, Response $response) {
    $errorMsg = (object) ['error' => "route not found"];
    return JsonResponse::withJson($response, json_encode($errorMsg), 404);
});

function executeQuery(Response $response, string $sql, string $field): Response {
    try {
        $db = new Database();
        $db = $db->connect();

        $stmt = $db->query( $sql );
        $fetchedObjects = $stmt->fetchAll( PDO::FETCH_OBJ );
        $db = null; // clear db object

        $result = array();
        foreach ($fetchedObjects as $value) {
            array_push($result, (string) $value->$field);
        }
        return JsonResponse::withJson($response, json_encode($result), 200);
    } catch( PDOException $e ) {
        $errorMsg = (object) ['error' => $e->getMessage()];
        return JsonResponse::withJson($response, json_encode($errorMsg), 500);
    }
}

function assembleContainsQueryFor(array $queryParams, string $item): string {
    $itemFilter = $queryParams[$item];
    $itemFilterStr = "%";
    if (!empty($itemFilter)) {
        $itemFilterStr = "%" . $itemFilter . "%";
    }
    return $itemFilterStr;
}

function assembleEqualsQueryFor(array $queryParams, string $item): string {
    $itemFilter = $queryParams[$item];
    $itemFilterStr = "%";
    if (!empty($itemFilter)) {
        $itemFilterStr = $itemFilter;
    }
    return $itemFilterStr;
}
