<?php

class Realtime {

    // The four technically supported "CRUD" operations.
    // Yes, I know, the R isn't for replace but read. Ignore that.
    const create = "CREATE";
    const replace = "REPLACE";
    const update = "UPDATE";
    const delete = "DELETE";

    // When a record is modified, this method adds a changelog entry
    // and notifies any listening clients of batched changes.
    public static function record($endpoint, $operation, $change) {
        $record = Dynamics::extract(__METHOD__, func_get_args());
        //$record['#modify_time'] = 'NOW()';
        $record['modify_user'] = Flight::get('user');
        $record['change'] = json_encode($change);

        // Execute the actual SQL query after confirming its formedness.
        try {
            Flight::db()->insert("Changelog", $record);
            log::transact(Flight::db()->last_query());

            // Get the last entered row's ID and return that.
            $id = Flight::db()->query("SELECT @@IDENTITY AS ID")->fetch();
            return $id['ID'];
        } catch(PDOException $e) {
            throw new HTTPException(log::err($e, Flight::db()->last_query()), 500);
        }
    }

    // Return all changes recorded since $since (>= 0), and if $since is null,
    // return the number of records found.
    public static function collect($since = 0) {
        $where = ["id[>]" => $since ?: 0,
                  "ORDER" => ["id" => "DESC"]];
        if ($since === null) {
            $where['LIMIT'] = 1;
        }

        // Execute the actual SQL query after confirming its formedness.
        try {
            $result = Flight::db()->select("Changelog", "*", $where);
            return $result;
        } catch(PDOException $e) {
            throw new HTTPException(log::err($e, Flight::db()->last_query()), 500);
        }
    }

    // An SSE implementation that pulls all changes since the last given ID.
    // If no ID is given, it returns only the last recorded ID. The client should
    // then cache this and use it when making future requests.
    public static function listen() {
        $lastId = filter_input(INPUT_SERVER, 'HTTP_LAST_EVENT_ID');
        if ($lastId) {
            foreach (Realtime::collect($lastId) as $message) {
                $stream
                    ->event()
                        ->setId($message['id'])
                        ->setEvent($message['endpoint'])
                        ->setData($message['data']);
                    ->end();
            }
        } else {
            $stream
                ->event()
                    ->setId(Realtime::collect(null))
                    ->setEvent('init');
                ->end();
        }
        $stream->flush();
    }
}

// Wrap Realtime::listen() by opening an EventStream.
Flight::route('/realtime', function() {
    set_time_limit(0);
    foreach (Stream::getHeaders() as $name => $value) {
        header("$name: $value");
    }

    Realtime::listen();
});
