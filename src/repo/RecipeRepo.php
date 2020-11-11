<?php
use Slim\Psr7\Stream;

class RecipeRepo
{
     public function insert(string $title, ?string $descr, ?string $tags, ?Stream $stream1, ?Stream $stream2, ?Stream $stream3) : string {
        $sql = "INSERT INTO recipes (title, description, tags, image1, image2, image3, created_at, updated_at) 
            VALUES(:title, :description, :tags, :image1, :image2, :image3, NOW(), NOW())";
        // echo "sql: $sql \n";

        try {
            $db = new Database();
            $db = $db->connect();
            $stmt = $db->prepare( $sql );

            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $descr);
            $stmt->bindParam(':tags', $tags);
            $stmt->bindParam(':image1', $stream1);
            $stmt->bindParam(':image2', $stream2);
            $stmt->bindParam(':image3', $stream3);

            $stmt->execute();
            return "";
        } catch( PDOException $e ) {
            return $e->getMessage();
        }
    }

    public function update(string $id, string $title, ?string $descr, ?string $tags, ?Stream $stream1, ?Stream $stream2, ?Stream $stream3) : string {
        $sql = "UPDATE recipes SET
                    title = :title, 
                    description = :description, 
                    tags = :tags,
                    image1 = :image1, 
                    image2 = :image2, 
                    image3 = :image3,
                    updated_at = NOW()
                WHERE id=$id";
        // echo "sql: $sql \n";

        try {
            $db = new Database();
            $db = $db->connect();
            $stmt = $db->prepare( $sql );

            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $descr);
            $stmt->bindParam(':tags', $tags);
            $stmt->bindParam(':image1', $stream1);
            $stmt->bindParam(':image2', $stream2);
            $stmt->bindParam(':image3', $stream3);

            $stmt->execute();
            return "";
        } catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
}