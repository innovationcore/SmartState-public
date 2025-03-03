<?php
require_once __DIR__ . '/../utilities/db.php';

class Metrics implements JsonSerializable {
    protected string $metricId;
    protected string $participantId;
    protected string $ts;
    protected string $json;


    public static function getTotalCompliance(): array {
        $query = "SELECT 
                    TO_CHAR(ts - INTERVAL '1 day', 'Dy') AS day_of_week,
                    SUM(CAST(metric_json->>'good_compliance' AS INTEGER)) AS total_good, 
                    SUM(CAST(metric_json->>'total_compliance' AS INTEGER)) AS total_total 
                  FROM metrics
                  GROUP BY day_of_week
                  ORDER BY MIN(ts)";
        $stmt = PostgresDB::run($query);
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = array(
                'day_of_week' => $row['day_of_week'],
                'total_good' => $row['total_good'],
                'total_total' => $row['total_total']
            );
        }
        return $results;
    }





    /**
     * @return string
     */
    public function getMetricId(): string {
        return $this->metricId;
    }

    /**
     * @param string $id
     */
    public function setMetricId(string $id): void {
        $this->metricId = $id;
    }

    /**
     * @return string
     */
    public function getParticipantId(): string {
        return $this->participantId;
    }

    /**
     * @param string $id
     */
    public function setParticipantId(string $id): void {
        $this->participantId = $id;
    }

    /**
     * @return string
     */
    public function getTimestamp(): string {
        return $this->ts;
    }

    /**
     * @param string $ts
     */
    public function setTimestamp(string $ts): void {
        $this->ts = $ts;
    }

    /**
     * @return string
     */
    public function getJson(): string {
        return $this->json;
    }

    /**
     * @param string $json
     * @return void
     */
    public function setJson(string $json): void {
        $this->json = $json;
    }

    /**
     * Encodes User Object into a JSON string
     * @return array
     */
    public function jsonSerialize(): array {
        return [
            'metricId'          => $this->getMetricId(),
            'participantId'     => $this->getParticipantId(),
            'ts'                => $this->getTimestamp(),
            'json'              => $this->getJson()
        ];
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return json_encode($this->jsonSerialize());
    }
}