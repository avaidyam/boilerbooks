<?php
    
    class Income {
        protected function __construct() {}
        protected function __clone() {}
        
        public static function add($organization, $year, $source, $type, $amount,
                                   $item, $status, $comments) {
            $income = get_defined_vars();
            $income["username"] = Flight::get('token')->username;
            
            // Ensure proper privileges to create a(n) (sub-)organization.
            if(!Flight::get('token')->root) {
                return Flight::json(["error" => "insufficient privileges to add an income"], 401);
            }
            
            // Execute the actual SQL query after confirming its formedness.
            try {
                Flight::db()->insert("Income", $income);
                return Flight::json(["result" => $income]);
            } catch(PDOException $e) {
                return Flight::json(["error" => $e->getMessage()], 500);
            }
        }
        
        public static function update($incomeid, $year = null, $source = null,
                                      $type = null, $item = null, $status = null, $comments = null) {
            $income = get_defined_vars();
            
            // Ensure proper privileges to create a(n) (sub-)organization.
            if(!Flight::get('token')->root) {
                return Flight::json(["error" => "insufficient privileges to update an income"], 401);
            }
            
            // Scrub the parameters into an updates array.
            $updates = array_filter($income, function($v, $k) { return !is_null($v); }, ARRAY_FILTER_USE_BOTH);
            unset($updates["incomeid"]);
            if (count($updates) == 0) {
                return Flight::json(["error" => "no updates to commit"], 400);
            }
            
            // Execute the actual SQL query after confirming its formedness.
            try {
                $result = Flight::db()->update("Income", $updates, ["incomeid" => $incomeid]);
                
                // Make sure 1 row was acted on, otherwise the income did not exist
                if ($result == 1) {
                    return Flight::json(["result" => $updates]);
                } else {
                    return Flight::json(["error" => "no such income available"], 404);
                }
            } catch(PDOException $e) {
                return Flight::json(["error" => $e->getMessage()], 500);
            }
        }
        
        public static function view($incomeid) {
            
            // Make sure we have rights to view the income.
            if (!Flight::get('token')->root) {
                return http_return(401, ["error" => "insufficient privileges to view an income"]);
            }
            
            // Execute the actual SQL query after confirming its formedness.
            try {
                $result = Flight::db()->select("Income", "*", ["incomeid" => $incomeid]);
                
                return Flight::json(["result" => $result]);
            } catch(PDOException $e) {
                return Flight::json(["error" => $e->getMessage()], 500);
            }
        }
        
        public static function search() {
            
            // Make sure we have rights to view the income.
            if (!Flight::get('token')->root) {
                return http_return(401, ["error" => "insufficient privileges to view an income"]);
            }
            
            // Execute the actual SQL query after confirming its formedness.
            try {
                $result = Flight::db()->select("Income", "*");
                
                return Flight::json(["result" => $result]);
            } catch(PDOException $e) {
                return Flight::json(["error" => $e->getMessage()], 500);
            }
        }
    }
    
    Flight::dynamic_route('GET /income/@incomeid', 'Income::view');
    Flight::dynamic_route('POST /income/@incomeid', 'Income::add');
    Flight::dynamic_route('PATCH /income/@incomeid', 'Income::update');
    Flight::dynamic_route('GET /incomes', 'Income::search');
?>
