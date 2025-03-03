<?php
require_once __DIR__ . '/../utilities/db.php';
require_once __DIR__ . '/../utilities/UUID.php';

class Survey {
    protected $token;
    protected $participant_uuid;
    protected $created_at;
    protected $finished_at;
    protected $survey_json;

    public function __construct() {
        $this->token = null;
        $this->participant_uuid = null;
        $this->created_at = null;
        $this->finished_at = null;
        $this->survey_json = null;
    }

    public static function delete($token): bool {
        if (empty($token) || is_null($token))
            throw new Exception('ID for survey not found.');
        $instance = new self();
        $status = $instance->deleteFromID($token);
        return $status;
    }

    public static function update($token, $participant_uuid, $created_at, $finished_at, $survey_json): Survey {
        if (empty($token))
            throw new Exception('You must provide an token ID to update a survey');
        $instance = Survey::withID($token);
        if (is_null($instance))
            throw new Exception("No survey found with token [{$token}]");
            $instance->setParticipant($participant_uuid);
            $instance->setCreatedAt($created_at);
            $instance->setFinishedAt($finished_at);
            $instance->setSurveyJSON($survey_json);
        return $instance->save();
    }

    public static function all(): array {
        $surveys = [];
        $query = "SELECT * FROM surveys";
        $stmt = PostgresDB::run($query);
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($surveys, Survey::withRow($row));
        return $surveys;
    }

    public static function saveSurveyContent($result, $token, $participantUUID): void {
        $survey_json = json_encode($result);
        $query = "UPDATE surveys SET finished_at=NOW(), survey_json=:survey_json WHERE token=:token AND participant_uuid=:uuid";
        $stmt = PostgresDB::prepare($query);
        $stmt->bindParam('survey_json', $survey_json);
        $stmt->bindParam('token', $token);
        $stmt->bindParam('uuid', $participantUUID);
        $stmt->execute();
    }

    public static function isSurveyFinished($token): bool {
        $isFinished = false;
        
        $query = "SELECT finished_at FROM surveys WHERE token=:token";
        $stmt = PostgresDB::prepare($query);
        $stmt->bindParam('token', $token);
        $stmt->execute();
        if($row = $stmt->fetch(PDO::FETCH_LAZY)){
            if(!is_null($row['finished_at'])){
                $isFinished = true;
            }
        }
        return $isFinished;
    }

    public static function countForDatatable(): int {
        $stmt = PostgresDB::run("SELECT count(token) FROM surveys");
        $count = $stmt->fetchColumn();
        return $count;
    }

    public static function countFilteredForDatatable(string $filter): int {
        $query = "SELECT count(token) FROM surveys";
        if (!is_null($filter) && strlen($filter) > 0) {
            $filter = "%{$filter}%";
            $query .= " WHERE (created_at LIKE :filter)";
        }
        $stmt = PostgresDB::prepare($query);
        if (!is_null($filter) && strlen($filter) > 0) {
            $stmt->bindParam('filter', $filter);
        }
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count;
    }

    public static function listForDatatable(int $start, int $length, string $order_by, string $order_dir, string $filter): array {
        $matches = [];

        $query = "SELECT * FROM surveys";
        $prune = '';
        if ($length > 0)
            $prune .= " OFFSET {$start} ROWS FETCH NEXT {$length} ROWS ONLY";
        if (is_null($order_by) || $order_by == '' || $order_by == '0')
            $order_by = 'finished_at';
        if (is_null($order_dir) || $order_dir == '')
            $order_dir = 'DESC';
        if (!is_null($filter) && strlen($filter) > 0) {
            $filter = '%' . $filter . '%';
            $query .= " WHERE (finished_at LIKE :filter)";
        }
        $query .= " ORDER BY CASE WHEN {$order_by} IS NOT NULL THEN {$order_by} ELSE created_at END {$order_dir}{$prune}";
        $stmt = PostgresDB::prepare($query);
        if (!is_null($filter) && strlen($filter) > 0) {
            $stmt->bindValue('filter', $filter);
        }
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_LAZY))
            array_push($matches, Survey::withRow($row));
        return $matches;
    }

    public static function checkValidToken($token): bool {
        $query = "SELECT count(token) FROM surveys WHERE token = :token";
        try{
            $stmt = PostgresDB::prepare($query);
            $stmt->bindParam('token', $token);
            
            $stmt->execute();
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function withID( $id ): ?Survey {
        try {
            $instance = new self();
            $instance->loadByID($id);
            return $instance;
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function withRow( $row ): Survey {
        $instance = new self();
        $instance->fill( $row );
        return $instance;
    }

    public static function getSurveyStats(): array {
        $matches = array();
        $totalQuery = PostgresDB::run("SELECT COUNT(token) AS total FROM surveys");
        if ($row = $totalQuery->fetch(PDO::FETCH_LAZY))
            $matches['total'] = $row['total'];
        else
            $matches['total'] = 'N/a';

        $activeQuery = PostgresDB::run("SELECT COUNT(token) AS completed FROM surveys WHERE finished_at IS NOT NULL");
        if ($row = $activeQuery->fetch(PDO::FETCH_LAZY))
            $matches['completed'] = $row['completed'];
        else
            $matches['completed'] = 'N/a';

        return $matches;
    }

    protected function loadByID( $token ): void {
        $stmt = PostgresDB::run(
            "SELECT * FROM surveys WHERE token = :token",
            ['token' => $token]
        );
        $row = $stmt->fetch(PDO::FETCH_LAZY);
        if ($row <> null)
            $this->fill( $row );
        else
            throw new PDOException("Survey with token [{$token}] not found");
    }

    protected function fill( $row ): void {
        $this->token = $row['token'];
        $this->participant_uuid = $row['participant_uuid'];
        $this->created_at = $row['created_at'];
        $this->finished_at = $row['finished_at'];
        $this->survey_json = $row['survey_json'];
    }

    protected function save(): ?Survey {
        $exists = Survey::withID($this->getID());
        if (is_null($exists)) {
            $insert = "INSERT INTO surveys (token, participant_uuid, created_at, finished_at, survey_json) VALUES (?,?,?,?,?)";
            PostgresDB::run($insert, [$this->getToken(), $this->getParticipant(), $this->getCreatedAt(), $this->getFinishedAt(), $this->getSurveyJSON()]);
        } else {
            $update = "UPDATE surveys SET participant_uuid=?, created_at=?, finished_at=?, survey_json=? WHERE token=?";
            PostgresDB::run($update, [$this->getParticipant(), $this->getCreatedAt(), $this->getFinishedAt(), $this->getSurveyJSON(), $this->getToken()]);
        }
        return Survey::withID($this->getID());
    }

    protected function deleteFromID( $token ): bool {
        $stmt = PostgresDB::prepare("DELETE FROM surveys WHERE token = :token");
        $stmt->bindParam('token', $token);
        $stmt->execute();
        if ($stmt->rowCount() > 0) 
            return true;
        else
            return false;
    }

    /**
     * @return string
     */
    public function getToken(): string {
        return $this->token;
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
    public function getCreatedAt(): string {
        return $this->created_at;
    }

    /**
     * @return string
     */
    public function getFinishedAt(): ?string {
        return $this->finished_at;
    }

    /**
     * @return string
     */
    public function getSurveyJSON(): ?string {
        return $this->survey_json;
    }

    /**
     * @param string $participant_uuid
     */
    public function setParticipant(string $participant_uuid): void {
        $this->participant_uuid = $participant_uuid;
    }

    /**
     * @param string $created_at
    */
    public function setCreatedAt(string $created_at): void {
        $this->created_at = $created_at;
    }

    /**
     * @param string $finished_at
    */
    public function setFinishedAt(string $finished_at): void {
        $this->finished_at = $finished_at;
    }

    /**
     * @param string $json
     */
    public function setSurveyJSON(string $survey_json): void {
        $this->survey_json = $survey_json;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize(): array {
        return [
            'token'             => $this->getToken(),
            'participant_uuid'  => $this->getParticipant(),
            'created_at'        => $this->getCreatedAt(),
            'finished_at'       => $this->getFinishedAt(),
            'survey_json'       => $this->getSurveyJSON()
        ];
    }
}