<?php

declare(strict_types=1);

final class Authentication
{
    const GOOGLE_CLIENT_DI = '288131662449-77rbf5afprn0u7nugord6k8hr7p63tjm.apps.googleusercontent.com';
    const IS_AUTH_ENABLED = false;
    const USE_DATABASE = true;
    const COOKIT_USERS = array(
        "christian.egli4@gmail.com",
        "joelle.egli@gmail.com",
        "zoe.egli@gmail.com",
        "liv.egli7@gmail.com");

    public static function authenticate(array $headerValueArray): string {
        if (!self::IS_AUTH_ENABLED) {
            return "";
        }

        if (count($headerValueArray) < 1) {
            return 'no token found';
        }
        $authHeaderInclBearer = array_values($headerValueArray)[0];
        $token = substr($authHeaderInclBearer, 7);
        $client = new Google_Client(['client_id' => self::GOOGLE_CLIENT_DI]);

        $payload = null;
		try {
			$payload = $client->verifyIdToken($token);
		} catch (BeforeValidException $e) {
            // The different Servertown servers are not in sync -> therefore the nbf/iat
            // claims are sometimes rejected: "Firebase\\JWT\\BeforeValidException: Cannot handle token prior to ..."
            // In order to avoid this, we wait some seconds before authentication
			error_log("BeforeValidException: " . $e, 0);
			sleep(5);
			$payload = $client->verifyIdToken($token);
		}
        //var_dump($payload);
        if ($payload) {
            $email = (string) $payload['email'];
            return self::checkUser($email, self::USE_DATABASE);
        } else {
            return 'token validation failed';
        }
    }

    public static function checkUser(string $email, bool $useDatabase): string {
        if ($useDatabase) {
            $sql = "SELECT * FROM users WHERE email='". $email . "'";
            try {
                $db = new Database();
                $db = $db->connect();

                $stmt = $db->query( $sql );
                $users = $stmt->fetchAll( PDO::FETCH_OBJ );
                $db = null; // clear db object

                if (count($users) < 1) {
                    return 'user ' . $email . ' not found';
                }
            } catch( PDOException $e ) {
                return $e->getMessage();
            }
        } else {
            // use static array
            if (!in_array($email, self::COOKIT_USERS)) {
                return 'user ' . $email . ' not found';
            }
        }
        return "";
    }
}
