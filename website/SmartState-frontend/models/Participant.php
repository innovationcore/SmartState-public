<?php
require_once __DIR__ . '/../utilities/db.php';
require_once __DIR__ . '/../utilities/UUID.php';

class Participant {
    protected $uuid;
    protected $json;

    public function __construct() {
        $this->uuid = UUID::v4();
        $this->json = null;
    }

    public static function listForDatatable(int $start, int $length, string $order_by, string $order_dir, string $filter): array {
        $matches = [];
        $query = "SELECT * FROM participants";
        $prune = '';
        if ($length > 0)
            $prune .= " OFFSET {$start} ROWS FETCH NEXT {$length} ROWS ONLY";
        if (is_null($order_by) || $order_by == '' || $order_by == '0')
            $order_by = 'participant_json';
        if (is_null($order_dir) || $order_dir == '')
            $order_dir = 'desc';
        if (!is_null($filter) && strlen($filter) > 0) {
            $filter = '%' . $filter . '%';
            $query .= " WHERE (name LIKE :filter)";
        }
        $query .= " ORDER BY {$order_by} {$order_dir}{$prune}";
        $stmt = DB::prepare($query);
        if (!is_null($filter) && strlen($filter) > 0) {
            $stmt->bindValue('filter', $filter);
        }
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($matches, Participant::withRow($row));
        return $matches;
    }

    public static function listStateForDatatable(string $uuid, string $protocol, int $start, int $length, string $order_by, string $order_dir, string $filter): array {
        $matches = [];
        $query = "SELECT TS, log_json FROM state_log";
        $prune = '';
        if ($length > 0)
            $prune .= " OFFSET {$start} ROWS FETCH NEXT {$length} ROWS ONLY";
        if (is_null($order_by) || $order_by == '' || $order_by == '0')
            $order_by = 'TS';
        if (is_null($order_dir) || $order_dir == '')
            $order_dir = 'desc';

        $query .= " WHERE (participant_uuid = :uuid) AND (JSON_VALUE(log_json, '$.protocol') = :protocol)";
        if (!is_null($filter) && strlen($filter) > 0) {
            $filter = '%' . $filter . '%';
            $query .= " AND (log_json LIKE :filter)";
        }
        $query .= " ORDER BY {$order_by} {$order_dir}{$prune}";
        $stmt = DB::prepare($query);
        if (!is_null($filter) && strlen($filter) > 0) {
            $stmt->bindValue('filter', $filter);
        }
        $stmt->bindValue('uuid', $uuid);
        $stmt->bindValue('protocol', $protocol);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($matches, ['TS' => $row['TS'], 'json' => $row['log_json']]);
        return $matches;
    }


    public static function create($json): ?Participant {
        if (empty($json))
            throw new Exception('You must provide a JSON object to create a participant');
        $instance = new self();
        $instance->setJSON($json);
        $participant = $instance->save();

        if(is_null($participant)) {
            return $participant;
        } else {
            $participant->enroll();
            return $participant;
        }    
    }

    public static function delete($uuid): bool {
        if (empty($uuid) || is_null($uuid))
            throw new Exception('ID for participant not found.');
        $instance = new self();
        $instance->deleteAllEnrollments();
        $status = $instance->deleteFromID($uuid);
        return $status;
    }

    protected function deleteEnrollment($enrollUUID): void {
        $query = "DELETE FROM enrollments WHERE enrollment_uuid = :uuid";
        $stmt = DB::prepare($query);
        $stmt->bindValue('uuid', $enrollUUID);
        $stmt->execute();
    }

    public static function update($uuid, $json): Participant {
        if (empty($uuid))
            throw new Exception('You must provide an id to update a participant');
        $instance = Participant::withID($uuid);
        if (is_null($instance))
            throw new Exception("No participant found with id [{$uuid}]");
        $instance->setJSON($json);
        $instance->updateEnrollment();
        return $instance->save();
    }

    protected function enroll(): void {
        foreach (json_decode($this->json)->group as $group) {
            $query = "INSERT INTO enrollments (enrollment_uuid, participant_uuid, protocol_type_uuid) VALUES (NEWID(), :participant_uuid, :group_uuid)";
            $stmt = DB::prepare($query);
            $stmt->bindValue('participant_uuid', $this->uuid);
            $stmt->bindValue('group_uuid', Participant::getUUIDFromGroup($group));
            $stmt->execute();
        }
        
    }

    protected function updateEnrollment(): void {
        $enrollments = $this->getEnrollmentUUID($this->uuid);
        $groups = json_decode($this->json)->group;
        $groupsEnrollUUIDs = [];
        foreach($groups as $group) {
            $temp = Participant::getEnrollmentUUIDFromProtocolUUID(Participant::getUUIDFromGroup($group));
            array_push($groupsEnrollUUIDs, $temp);
        }

        // removes enrollments that are not in updated group
        foreach($enrollments as $enrollment){
            if (!in_array($enrollment, $groupsEnrollUUIDs)){
                $this->deleteEnrollment($enrollment);
            }
        }

        $enrollments = $this->getEnrollmentUUID($this->uuid); // update enrollments, if any were deleted

        // inserts new enrollment if it doesn't already exist
        foreach ($groups as $group){
            $newEnrollmentUUID = Participant::getEnrollmentUUIDFromProtocolUUID(Participant::getUUIDFromGroup($group));
            if (!in_array($newEnrollmentUUID, $enrollments)){
                $query = "INSERT INTO enrollments (enrollment_uuid, participant_uuid, protocol_type_uuid) VALUES (NEWID(), :participant_uuid, :group_uuid)";
                $stmt = DB::prepare($query);
                $stmt->bindValue('participant_uuid', $this->uuid);
                $stmt->bindValue('group_uuid', Participant::getUUIDFromGroup($group));
                $stmt->execute();
            }
        }
    }

    protected function getEnrollmentUUID(): array {
        $enrollments = [];
        $query = "SELECT enrollment_uuid FROM enrollments WHERE participant_uuid = :participant_uuid";
        $stmt = DB::prepare($query);
        $stmt->bindValue('participant_uuid', $this->uuid);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($enrollments, $row['enrollment_uuid']);
        return $enrollments;
    }

    protected function deleteAllEnrollments(): void{
        $query = "DELETE FROM enrollments WHERE participant_uuid = :uuid";
        $stmt = DB::prepare($query);
        $stmt->bindValue('uuid', $this->uuid);
        $stmt->execute();
    }

    public static function all(): array {
        $participants = [];
        $query = "SELECT * FROM participants";
        $stmt = DB::run($query);
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($participants, Participant::withRow($row));
        return $participants;
    }

    public static function allProtocols(): array {
        $protocols = [];
        $query = "SELECT * FROM protocol_types";
        $stmt = DB::run($query);
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($protocols, ProtocolType::withRow($row));
        return $protocols;
    }

    public static function allLocations(): array {
        $locations = [];
        $query = "SELECT DISTINCT short_zone FROM time_zones";
        $stmt = DB::run($query);
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($locations, ['location' => $row['short_zone']]);
        return $locations;
    }

    public static function countForDatatable(): int {
        $stmt = DB::run("SELECT count(participant_uuid) FROM participants");
        $count = $stmt->fetchColumn();
        return $count;
    }

    public static function countFilteredForDatatable(string $filter): int {
        $query = "SELECT count(participant_uuid) FROM participants";
        if (!is_null($filter) && strlen($filter) > 0) {
            $filter = "%{$filter}%";
            $query .= " WHERE (participant_json LIKE :filter)";
        }
        $stmt = DB::prepare($query);
        if (!is_null($filter) && strlen($filter) > 0) {
            $stmt->bindParam('filter', $filter);
        }
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count;
    }

    public static function countForStateDatatable(string $uuid): int {
        $stmt = DB::run("SELECT count(participant_uuid) FROM state_log WHERE participant_uuid = :uuid", ['uuid' => $uuid]);
        $count = $stmt->fetchColumn();
        return $count;
    }

    public static function countFilteredForStateDatatable(string $uuid, string $filter): int {
        $query = "SELECT count(participant_uuid) FROM state_log WHERE participant_uuid = :uuid";
        if (!is_null($filter) && strlen($filter) > 0) {
            $filter = "%{$filter}%";
            $query .= " AND (log_json LIKE :filter)";
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


    public static function getConcatNameFromID($id): string {
        $stmt = DB::run("SELECT CONCAT_WS(' ', JSON_VALUE(participant_json,'$.first_name'), JSON_VALUE(participant_json,'$.last_name')) AS participant_name FROM participants WHERE participant_uuid = :id",
            ['id' => $id]
        );
        $row = $stmt->fetch();
        return $row['participant_name'];
    }

    public static function getGroupFromUUID($uuid): string {
        $stmt = DB::run("SELECT JSON_VALUE(participant_json,'$.group') AS group_name FROM participants WHERE participant_uuid = :uuid",
            ['uuid' => $uuid]
        );
        $row = $stmt->fetch();
        return $row['group_name'];
    }

    public static function getUUIDFromGroup($group): string {
        $stmt = DB::run("SELECT protocol_type_uuid FROM protocol_types WHERE name = :group",
            ['group' => $group]
        );
        $row = $stmt->fetch();
        return $row['protocol_type_uuid'];
    }

    public static function getTimeZoneFromUUID($uuid): string {
        $stmt = DB::run("SELECT JSON_VALUE(participant_json,'$.time_zone') AS time_zone FROM participants WHERE participant_uuid = :uuid",
            ['uuid' => $uuid]
        );
        $row = $stmt->fetch();
        return $row['time_zone'];
    }

    public static function getZonesWithLocation( $location ): array {
        $zones = [];
        $stmt = DB::run("SELECT time_zone FROM time_zones WHERE short_zone= :location",
            ['location' => $location]
        );
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($zones, ['name' => $row['time_zone']]);
        return $zones;
    }

    public static function withID( $uuid ): ?Participant {
        try {
            $instance = new self();
            $instance->loadByID($uuid);
            return $instance;
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function withRow( $row ): Participant {
        $instance = new self();
        $instance->fill( $row );
        return $instance;
    }

    public static function getPhoneNumber($uuid): ?string {
        try {
            $stmt = DB::run("SELECT JSON_VALUE(participant_json,'$.number') AS phone_number FROM participants WHERE participant_uuid = :uuid",
                ['uuid' => $uuid]
            );
            $row = $stmt->fetch();
            return $row['phone_number'];
        } catch (PDOException $e) {
            return null;
        }
    }

    protected function getEnrollmentUUIDFromProtocolUUID($protocolUUID): ?string {
        try {
            $stmt = DB::run("SELECT enrollment_uuid FROM enrollments WHERE protocol_type_uuid = :uuid",
                ['uuid' => $protocolUUID]
            );
            $row = $stmt->fetch();
            return $row['enrollment_uuid'];
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function getCurrentStateString($uuid, $protocol): ?string {
        $stmt = DB::run("SELECT TOP 1 JSON_VALUE(log_json,'$.state') AS state FROM state_log WHERE participant_uuid = :uuid  AND JSON_VALUE(log_json, '$.protocol') = :protocol ORDER BY TS DESC",
            ['uuid' => $uuid, 'protocol' => $protocol]
        );
        $row = $stmt->fetch();
        if (empty($row['state']) || is_null($row['state']))
            return null;
        return $row['state'];
    }

    public static function getTimeZone($uuid): ?string {
        try {
            $stmt = DB::run("SELECT JSON_VALUE(participant_json,'$.time_zone') AS time_zone FROM participants WHERE participant_uuid = :uuid",
                ['uuid' => $uuid]
            );
            $row = $stmt->fetch();
            return $row['time_zone'];
        } catch (PDOException $e) {
            return null;
        }
    }

    protected function loadByID( $uuid ): void {
        $stmt = DB::run(
            "SELECT * FROM participants WHERE participant_uuid = :id ORDER BY participant_uuid OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY",
            ['id' => $uuid]
        );
        $row = $stmt->fetch(PDO::FETCH_LAZY);
        if ($row <> null)
            $this->fill( $row );
        else
            throw new PDOException("Participant with id [{$uuid}] not found");
    }

    protected function fill( $row ): void {
        $this->uuid = $row['participant_uuid'];
        $this->json = $row['participant_json'];
    }

    protected function save(): ?Participant {
        $exists = Participant::withID($this->getID());
        if (is_null($exists)) {
            $insert = "INSERT INTO participants (participant_uuid, participant_json) VALUES (?,?)";
            DB::run($insert, [$this->getID(), $this->getJSON()]);
        } else {
            $update = "UPDATE participants SET participant_json=? WHERE participant_uuid=?";
            DB::run($update, [$this->getJSON(), $this->getID()]);
        }
        return Participant::withID($this->getID());
    }

    protected function deleteFromID( $uuid ): bool {
        $stmt = DB::prepare("DELETE FROM participants WHERE participant_uuid = :uuid");
        $stmt->bindParam('uuid', $uuid);
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
    public function getJSON(): string {
        return $this->json;
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
            'uuid'      => $this->getID(),
            'json'      => $this->getJSON()
        ];
    }
}