<?php
use Slim\Psr7\Stream;

class RecipeRepo
{
     public function insert(string $title, ?string $descr, ?string $category, ?string $effort, ?string $tags, ?Stream $stream1, ?Stream $stream2, ?Stream $stream3) : string {
         $sql1 = "INSERT INTO recipes (title, description, category, effort, image1, image2, image3, created_at, updated_at) 
            VALUES(:title, :description, :category, :effort, :image1, :image2, :image3, NOW(), NOW())";
         // echo "sql1: $sql1 \n";
         $sql2 = "INSERT INTO tags (name, recipe_id) VALUES (:name, :recipe_id)";
         // echo "sql2: $sql2 \n";

         try {
            $db = new Database();
            $db = $db->connect();
            $db->beginTransaction();

            $stmt = $db->prepare( $sql1 );
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $descr);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':effort', $effort);
            $stmt->bindParam(':image1', $stream1);
            $stmt->bindParam(':image2', $stream2);
            $stmt->bindParam(':image3', $stream3);
            $stmt->execute();

            if ($tags != null) {
                $lastInsertedId = $db->lastInsertId();
                //echo "lastInsertedId: $lastInsertedId \n";

                $array = explode(',', $tags); //split $tags into array separated by ','
                foreach($array as $value)
                {
                    $stmt = $db->prepare( $sql2 );
                    $stmt->bindParam(':name', $value);
                    $stmt->bindParam(':recipe_id', $lastInsertedId);
                    $stmt->execute();
                }
            }

            $db->commit();
            return "";
        } catch( PDOException $e ) {
            return $e->getMessage();
        }
    }

    public function update(string $id, string $title, ?string $descr, ?string $category, ?string $effort, ?string $tags, ?Stream $stream1, ?Stream $stream2, ?Stream $stream3) : string {
        $sql = "UPDATE recipes SET
                    title = :title, 
                    description = :description, 
                    category = :category,
                    effort = :effort,
                    image1 = :image1, 
                    image2 = :image2, 
                    image3 = :image3,
                    updated_at = NOW()
                WHERE id = :id";
        // echo "sql: $sql \n";

        try {
            $db = new Database();
            $db = $db->connect();
            $db->beginTransaction();

            $stmt = $db->prepare( $sql );
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $descr);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':effort', $effort);
            $stmt->bindParam(':image1', $stream1);
            $stmt->bindParam(':image2', $stream2);
            $stmt->bindParam(':image3', $stream3);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $stmt = $db->prepare( "DELETE FROM tags WHERE recipe_id = :id;" );
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($tags != null) {
                $array = explode(',', $tags);
                foreach($array as $value)
                {
                    $sql2 = "INSERT INTO tags (name, recipe_id) VALUES (:name, :recipe_id)";
                    $stmt = $db->prepare( $sql2 );
                    $stmt->bindParam(':name', $value);
                    $stmt->bindParam(':recipe_id', $id);
                    $stmt->execute();
                }
            }

            $db->commit();
            return "";
        } catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
}