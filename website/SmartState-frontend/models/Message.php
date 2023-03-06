<?php
require_once __DIR__ . '/../utilities/db.php';
require_once __DIR__ . '/../utilities/UUID.php';
include_once MODELS_DIR . 'Participant.php';

use Twilio\Rest\Client;

class Message {
    protected $uuid;
    protected $participant_uuid;
    protected $timestamp;
    protected $direction;
    protected $json;

    const OUTGOING = 'outgoing';
    const INCOMING = 'incoming';

    public function __construct() {
        $this->uuid = UUID::v4();
        $this->participant_uuid = null;
        $this->timestamp = null;
        $this->direction = null;
        $this->json = null;
    }

    public static function create($participant_uuid, $timestamp, $direction, $json): ?Message {
        if (empty($participant_uuid) || empty($timestamp) || empty($direction) || empty($json))
            throw new Exception('You must provide participant_uuid, timestamp, direction, and JSON to create a message');
        $instance = new self();
        $instance->setParticipant($participant_uuid);
        $instance->setTimestamp($timestamp);
        $instance->setDirection($direction);
        $instance->setJSON($json);
        return $instance->save();
    }

    public static function delete($id): bool {
        if (empty($id) || is_null($id))
            throw new Exception('ID for message not found.');
        $instance = new self();
        $status = $instance->deleteFromID($id);
        return $status;
    }

    public static function update($id, $participant_uuid, $timestamp, $direction, $json): Message {
        if (empty($id))
            throw new Exception('You must provide an id to update a message');
        $instance = Message::withID($id);
        if (is_null($instance))
            throw new Exception("No message found with id [{$id}]");
            $instance->setParticipant($participant_uuid);
            $instance->setTimestamp($timestamp);
            $instance->setDirection($direction);
            $instance->setJSON($json);
        return $instance->save();
    }

    public static function all(): array {
        $messages = [];
        $query = "SELECT * FROM messages";
        $stmt = DB::run($query);
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($messages, Message::withRow($row));
        return $messages;
    }

    public static function countForDatatable(): int {
        $stmt = DB::run("SELECT count(message_uuid) FROM messages");
        $count = $stmt->fetchColumn();
        return $count;
    }

    public static function countFilteredForDatatable(string $filter): int {
        $query = "SELECT count(message_uuid) FROM messages";
        if (!is_null($filter) && strlen($filter) > 0) {
            $filter = "%{$filter}%";
            $query .= " WHERE (TS LIKE :filter)";
        }
        $stmt = DB::prepare($query);
        if (!is_null($filter) && strlen($filter) > 0) {
            $stmt->bindParam('filter', $filter);
        }
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count;
    }

    public static function listForDatatable(int $start, int $length, string $order_by, string $order_dir, string $filter): array {
        $matches = [];

        $query = "SELECT message_uuid, participant_uuid, TS, message_direction, message_json FROM messages";
        $prune = '';
        if ($length > 0)
            $prune .= " OFFSET {$start} ROWS FETCH NEXT {$length} ROWS ONLY";
        if (is_null($order_by) || $order_by == '' || $order_by == '0')
            $order_by = 'TS';
        if (is_null($order_dir) || $order_dir == '')
            $order_dir = 'desc';
        if (!is_null($filter) && strlen($filter) > 0) {
            $filter = '%' . $filter . '%';
            $query .= " WHERE (TS LIKE :filter)";
        }
        $query .= " ORDER BY {$order_by} {$order_dir}{$prune}";
        $stmt = DB::prepare($query);
        if (!is_null($filter) && strlen($filter) > 0) {
            $stmt->bindValue('filter', $filter);
        }
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($matches, Message::withRow($row));
        return $matches;
    }

    public static function countIndividualForDatatable(string $uuid): int {
        $stmt = DB::run("SELECT count(message_uuid) FROM messages WHERE participant_uuid = :uuid", ['uuid' => $uuid]);
        $count = $stmt->fetchColumn();
        return $count;
    }

    public static function countIndividualFilteredForDatatable(string $uuid, string $filter): int {
        $query = "SELECT count(message_uuid) FROM messages WHERE (participant_uuid = :uuid)";
        if (!is_null($filter) && strlen($filter) > 0) {
            $filter = "%{$filter}%";
            $query .= " AND (TS LIKE :filter)";
        }
        $stmt = DB::prepare($query);
        $stmt->bindParam('uuid', $uuid);
        if (!is_null($filter) && strlen($filter) > 0) {
            $stmt->bindParam('filter', $filter);
        }
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count;
    }

    public static function listIndividualForDatatable(string $uuid, int $start, int $length, string $order_by, string $order_dir, string $filter): array {
        $matches = [];
        $query = "SELECT message_uuid, participant_uuid, TS, message_direction, message_json FROM messages";
        $prune = '';
        if ($length > 0)
            $prune .= " OFFSET {$start} ROWS FETCH NEXT {$length} ROWS ONLY";
        if (is_null($order_by) || $order_by == '' || $order_by == '0')
            $order_by = 'TS';
        if (is_null($order_dir) || $order_dir == '')
            $order_dir = 'desc';

        $query .= " WHERE (participant_uuid = :uuid)";
        if (!is_null($filter) && strlen($filter) > 0) {
            $filter = '%' . $filter . '%';
            $query .= " AND (TS LIKE :filter)";
        }
        $query .= " ORDER BY {$order_by} {$order_dir}{$prune}";
        $stmt = DB::prepare($query);
        $stmt->bindValue('uuid', $uuid);
        if (!is_null($filter) && strlen($filter) > 0) {
            $stmt->bindValue('filter', $filter);
        }
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($matches, Message::withRow($row));
        return $matches;
    }
    

    public static function withID( $id ): ?Message {
        try {
            $instance = new self();
            $instance->loadByID($id);
            return $instance;
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function allForCSV($user_role): array {
        // if user_role is 1 (non-PHI), remove all PHI from csv
        $messages = [];
        // append column names
        if ($user_role == 1){
            array_push($messages, [
                'Message UUID',
                'Participant UUID',
                'Timestamp',
                'Direction',
                'Body'
            ]);
            $query = "SELECT 
                    m.message_uuid, 
                    p.participant_uuid, 
                    m.TS, 
                    m.message_direction, 
                    JSON_VALUE(m.message_json, '$.Body') AS body 
                FROM messages AS m 
                INNER JOIN participants AS p ON p.participant_uuid = m.participant_uuid";
            $stmt = DB::run($query);
            while ($row = $stmt->fetch(PDO::FETCH_LAZY))
                array_push($messages, Message::fillCSVRow($row, $user_role));
            return $messages;
        } else {
            array_push($messages, [
                'Message UUID',
                'Participant UUID',
                'Participant Name',
                'Timestamp',
                'Direction',
                'Body',
                'To/From'
            ]);
            $query = "SELECT 
                    m.message_uuid, 
                    p.participant_uuid, 
                    m.TS, 
                    m.message_direction, 
                    JSON_VALUE(m.message_json, '$.Body') AS body, 
                    JSON_VALUE(p.participant_json, '$.number') AS number, 
                    CONCAT_WS(' ', JSON_VALUE(p.participant_json,'$.first_name'), JSON_VALUE(p.participant_json,'$.last_name')) AS participant_name 
                FROM messages AS m 
                INNER JOIN participants AS p ON p.participant_uuid = m.participant_uuid";
            $stmt = DB::run($query);
            while ($row = $stmt->fetch(PDO::FETCH_LAZY))
                array_push($messages, Message::fillCSVRow($row, $user_role));
            return $messages;
        }
    }

    public static function fillCSVRow($row, $user_role): array {
        if($user_role == 1){
            return [
                $row['message_uuid'],
                $row['participant_uuid'],
                date('m/d/Y, H:i:s', strtotime($row['TS'])),
                $row['message_direction'],
                $row['body']
            ];
        } else {
            return [
                $row['message_uuid'],
                $row['participant_uuid'],
                $row['participant_name'],
                date('m/d/Y, H:i:s', strtotime($row['TS'])),
                $row['message_direction'],
                $row['body'],
                $row['number']
            ];
        } 
    }

    public static function send($messageObj, $participantNumber): ?bool {
        try {
            if(is_null($messageObj) || is_null($participantNumber))
                throw new Exception('You must provide a message object and a participant number to send a message');
            $config = include CONFIG_FILE;
            $sid = $config['twilio']['account_sid'];
            $token = $config['twilio']['auth_token'];
            $fromNumber = $config['twilio']['from_number'];
            $participantNumber = Participant::getPhoneNumber($messageObj->getParticipant());
            $message = $messageObj->getMessage();
            $client = new Client($sid, $token);

            $response = $client->messages->create(
                $participantNumber,
                [
                    'from' => $fromNumber,
                    'body' => $message
                ]
            );

            $messageObj->save();
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    public static function withRow( $row ): Message {
        $instance = new self();
        $instance->fill( $row );
        return $instance;
    }

    protected function loadByID( $id ): void {
        $stmt = DB::run(
            "SELECT * FROM messages WHERE message_uuid = :id ORDER BY message_uuid OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY",
            ['id' => $id]
        );
        $row = $stmt->fetch(PDO::FETCH_LAZY);
        if ($row <> null)
            $this->fill( $row );
        else
            throw new PDOException("Message with id [{$id}] not found");
    }

    protected function fill( $row ): void {
        $this->uuid = $row['message_uuid'];
        $this->participant_uuid = $row['participant_uuid'];
        $this->timestamp = $row['TS'];
        $this->direction = $row['message_direction'];
        $this->json = $row['message_json'];
    }

    protected function save(): ?Message {
        $exists = Message::withID($this->getID());
        if (is_null($exists)) {
            $insert = "INSERT INTO messages (message_uuid, participant_uuid, TS, message_direction, message_json) VALUES (?,?,?,?,?)";
            DB::run($insert, [$this->getID(), $this->getParticipant(), $this->getTimestamp(), $this->getDirection(), $this->getJSON()]);
        } else {
            $update = "UPDATE messages SET participant_uuid=?, TS=?, message_direction=?, message_json=? WHERE message_uuid=?";
            DB::run($update, [$this->getParticipant(), $this->getTimestamp(), $this->getDirection(), $this->getJSON(), $this->getID()]);
        }
        return Message::withID($this->getID());
    }

    protected function getMessage(): string {
        $json = json_decode(Message::getJSON(), true);
        $body = $json['Body'];
        return $body;
    }

    protected function deleteFromID( $id ): bool {
        $stmt = DB::prepare("DELETE FROM messages WHERE message_uuid = :id");
        $stmt->bindParam('id', $id);
        $stmt->execute();
        if ($stmt->rowCount() > 0) 
            return true;
        else
            return false;
    }

    /**
     * @return string
     */
    public function getID(): string {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getParticipant(): string {
        return $this->participant_uuid;
    }

    /**
     * @return string
     */
    public function getTimestamp(): string {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getDirection(): string {
        return $this->direction;
    }

    /**
     * @return string
     */
    public function getJSON(): string {
        return $this->json;
    }

    /**
     * @param string $participant_uuid
     */
    public function setParticipant(string $participant_uuid): void {
        $this->participant_uuid = $participant_uuid;
    }

    /**
     * @param string $timestamp
     */
    public function setTimestamp(string $timestamp): void {
        $this->timestamp = $timestamp;
    }

    /**
     * @param string $direction
     */
    public function setDirection(string $direction): void {
        $this->direction = $direction;
    }

    /**
     * @param string $json
     */
    public function setJSON(string $json): void {
        $this->json = $json;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize(): array {
        return [
            'uuid'              => $this->getID(),
            'participant_uuid'  => $this->getParticipant(),
            'TS'                => $this->getTimestamp(),
            'direction'         => $this->getDirection(),
            'json'              => $this->getJSON()
        ];
    }
}