package SmartState.Database;

import SmartState.Launcher;
import com.google.gson.Gson;
import org.apache.commons.dbcp2.*;
import org.apache.commons.pool2.ObjectPool;
import org.apache.commons.pool2.impl.GenericObjectPool;

import javax.sql.DataSource;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

public class DBEngine {
    private Gson gson;
    private DataSource ds;
    public DBEngine() {

        try {
            gson = new Gson();
            //Driver needs to be identified in order to load the namespace in the JVM
            String dbDriver = "com.microsoft.sqlserver.jdbc.SQLServerDriver";

            Class.forName(dbDriver).newInstance();

            String dbConnectionString = "jdbc:sqlserver://" + Launcher.config.getStringParam("db_host") + ":" + 1433 + ";databaseName=" + Launcher.config.getStringParam("db_name") + ";encrypt=false";

            ds = setupDataSource(dbConnectionString, Launcher.config.getStringParam("db_user"), Launcher.config.getStringParam("db_password"));
        } catch (Exception ex) {
            ex.printStackTrace();
        }
    }

    public static DataSource setupDataSource(String connectURI, String login, String password) {
        //
        // First, we'll create a ConnectionFactory that the
        // pool will use to create Connections.
        // We'll use the DriverManagerConnectionFactory,
        // using to connect string passed in the command line
        // arguments.
        //
        ConnectionFactory connectionFactory;
        if((login == null) && (password == null)) {
            connectionFactory = new DriverManagerConnectionFactory(connectURI, null);
        } else {
            connectionFactory = new DriverManagerConnectionFactory(connectURI,
                    login, password);
        }

        //
        // Next we'll create the PoolableConnectionFactory, which wraps
        // the "real" Connections created by the ConnectionFactory with
        // the classes that implement the pooling functionality.
        //
        PoolableConnectionFactory poolableConnectionFactory =
                new PoolableConnectionFactory(connectionFactory, null);

        //
        // Now we'll need a ObjectPool that serves as the
        // actual pool of connections.
        //
        // We'll use a GenericObjectPool instance, although
        // any ObjectPool implementation will suffice.
        //
        ObjectPool<PoolableConnection> connectionPool =
                new GenericObjectPool<>(poolableConnectionFactory);

        // Set the factory's pool property to the owning pool
        poolableConnectionFactory.setPool(connectionPool);

        //
        // Finally, we create the PoolingDriver itself,
        // passing in the object pool we created.
        //
        return new PoolingDataSource<>(connectionPool);
    }


    public String getParticipantIdFromPhoneNumber(String PhoneNumber) {
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        String participantId = null;
        try {
            String queryString = "SELECT participant_uuid FROM participants WHERE JSON_VALUE(participant_json, '$.number') = ?";

            conn = ds.getConnection();
            stmt = conn.prepareStatement(queryString);
            stmt.setString(1, PhoneNumber);
            rs = stmt.executeQuery();

            if (rs.next()) {
                participantId = rs.getString("participant_uuid");
            }

        } catch(Exception ex) {
            ex.printStackTrace();
        } finally {
            try { rs.close(); }   catch (Exception e) { /* Null Ignored */ }
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
        }
        return participantId;
    }

    public List<Map<String,String>> getParticipantMapByGroup(String groupName) {
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        List<Map<String,String>> participantMaps = null;
        try {
            participantMaps = new ArrayList<>();

            String queryString = "SELECT participant_uuid, participant_json FROM participants CROSS APPLY OPENJSON(participant_json, '$.group') groups WHERE groups.Value = ?";

            conn = ds.getConnection();
            stmt = conn.prepareStatement(queryString);
            stmt.setString(1, groupName);
            rs = stmt.executeQuery();

            while (rs.next()) {
                //Map<String,String> participantMap = new HashMap<>();
                Map<String,String> participantMap = gson.fromJson(rs.getString("participant_json"), Map.class);
                participantMap.put("participant_uuid",rs.getString("participant_uuid"));
                participantMaps.add(participantMap);
            }

        } catch(Exception ex) {
            ex.printStackTrace();
        } finally {
            try { rs.close(); }   catch (Exception e) { /* Null Ignored */ }
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
        }

        return participantMaps;
    }


    public int executeUpdate(String stmtString) {
        Connection conn = null;
        Statement stmt = null;
        int result = -1;
        try {
            conn = ds.getConnection();
            stmt = conn.createStatement();
            result = stmt.executeUpdate(stmtString);

        } catch(Exception ex) {
            ex.printStackTrace();
        } finally {
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
        }
        return result;
    }

    public String getEnrollmentUUIDFromName(String name){
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        String result = "";
        try {
            String query = "SELECT enrollment_uuid FROM enrollments WHERE protocol_type_uuid IN (SELECT protocol_type_uuid FROM protocol_types WHERE name = ?)";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query);
            stmt.setString(1, name);
            rs = stmt.executeQuery();

            if (rs.next()) {
                result = rs.getString(1);
            }
        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
            try { rs.close();   } catch (Exception e) { /* Null Ignored */ }
        }
        return result;
    }

    public ArrayList<String> getEnrollmentUUID(String uuid){
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        ArrayList<String> result = new ArrayList<>();
        try {
            String query = "SELECT enrollment_uuid FROM enrollments WHERE participant_uuid = ?";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query);
            stmt.setString(1, uuid);
            rs = stmt.executeQuery();

            while (rs.next()) {
                result.add(rs.getString(1));
            }
        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
            try { rs.close();   } catch (Exception e) { /* Null Ignored */ }
        }
        return result;
    }

    public String getEnrollmentName(String enrollUUID) {
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        String result = "";

        try {
            String query = "SELECT name FROM protocol_types WHERE protocol_type_uuid IN (SELECT protocol_type_uuid FROM enrollments WHERE enrollment_uuid = ?)";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query);
            stmt.setString(1, enrollUUID);
            rs = stmt.executeQuery();
            if (rs.next()) {
                result = rs.getString(1);
            }

        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
            try { rs.close();   } catch (Exception e) { /* Null Ignored */ }
        }
        return result;
    }

    // remove all but latest 50 rows from save state
    public void pruneSaveStateEntries(String enrollment_uuid) {
        Connection conn = null;
        PreparedStatement stmt = null;
        try {
            String query = "DELETE FROM save_state WHERE TS NOT IN (SELECT TOP 50 TS FROM save_state WHERE enrollment_uuid = ? ORDER BY TS DESC) AND enrollment_uuid = ?";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query);
            stmt.setString(1, enrollment_uuid);
            stmt.setString(2, enrollment_uuid);
            stmt.executeUpdate();
        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
        }
    }

    public void uploadSaveState(String stateJSON, String enrollment, String participant_uuid){
        Connection conn = null;
        PreparedStatement stmt = null;
        try {
            String query;
            conn = ds.getConnection();
            ArrayList<String> enrollment_uuids = getEnrollmentUUID(participant_uuid);
            for (String enrollUUID: enrollment_uuids){
                if (getEnrollmentName(enrollUUID).equals(enrollment)){
                    pruneSaveStateEntries(enrollUUID);
                    query = "INSERT INTO save_state (enrollment_uuid, TS, state_json) VALUES (?, GETUTCDATE(), ?)";
                    stmt = conn.prepareStatement(query);
                    stmt.setString(1, enrollUUID);
                    stmt.setString(2, stateJSON);
                    stmt.executeUpdate();
                }
            }
            
        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
        }
    }

    public String getParticipantCurrentState(String partUUID, String protocol) {
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        String result = "";
        try {
            String query = "SELECT TOP 1 JSON_VALUE(log_json, '$.state') FROM state_log WHERE participant_uuid = ? AND JSON_VALUE(log_json, '$.protocol') = ? ORDER BY TS DESC";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query);
            stmt.setString(1, partUUID);
            stmt.setString(2, protocol);
            rs = stmt.executeQuery();

            if (rs.next()) {
                result = rs.getString(1);
            }
        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
            try { rs.close();   } catch (Exception e) { /* Null Ignored */ }
        }
        return result;
    }

    public String getSaveState(String participantUUID, String enrollment) {
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        String json = "";
        try {
            String query = "SELECT TOP 1 state_json FROM save_state WHERE enrollment_uuid = ? ORDER BY TS DESC";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query);
            stmt.setString(1, getEnrollmentUUID(participantUUID, enrollment));
            rs = stmt.executeQuery();

            if (rs.next()) {
                json = rs.getString(1);
            }
        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
            try { rs.close();   } catch (Exception e) { /* Null Ignored */ }
        }
        return json;
    }

    public String getEnrollmentUUID(String uuid, String enrollment){
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        String result = null;
        try {
            String query = "SELECT enrollment_uuid FROM enrollments WHERE participant_uuid = ? AND enrollment_uuid = ?";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query);
            stmt.setString(1, uuid);
            stmt.setString(2, getEnrollmentUUIDFromName(enrollment));
            rs = stmt.executeQuery();

            if (rs.next()) {
                result = rs.getString(1);
            }
        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
            try { rs.close();   } catch (Exception e) { /* Null Ignored */ }
        }
        return result;
    }

    public ArrayList<String> getAdminNumbers() {
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        ArrayList<String> adminNumbers = new ArrayList<>();

        try {
            String query = "SELECT phone_number FROM users WHERE role IN (SELECT id FROM user_roles WHERE role_name = 'Study Admin')";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query);
            rs = stmt.executeQuery();

            while (rs.next()) {
                adminNumbers.add(rs.getString(1));
            }
        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { rs.close(); }   catch (Exception e) { /* Null Ignored */ }
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
        }
        return adminNumbers;
    }

    public String generateAndSaveToken(String participantUUID) {
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        String token = "";

        try {
            String query = "INSERT INTO surveys (token, participant_uuid, created_at) OUTPUT inserted.token VALUES (NEWID(), ?, GETUTCDATE())";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query, Statement.RETURN_GENERATED_KEYS);
            stmt.setString(1, participantUUID);
            rs = stmt.executeQuery();
            
            if (rs.next()) {
                token = rs.getString(1);
            }

        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { rs.close(); }   catch (Exception e) { /* Null Ignored */ }
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
        }
        return token;
    }

    public String getParticipantIdFromDevice(String devEUI) {
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        String participantId = "";

        try {
            String query = "SELECT participant_uuid FROM participants WHERE JSON_VALUE(participant_json, '$.devEUI') = ?";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query);
            stmt.setString(1, devEUI.toLowerCase());
            rs = stmt.executeQuery();

            if (rs.next()) {
                participantId = rs.getString("participant_uuid");
            }

        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { rs.close(); }   catch (Exception e) { /* Null Ignored */ }
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
        }
        return participantId;
    }

    public void saveGlucoseResults(String participantUUID, String result) {
        Connection conn = null;
        PreparedStatement stmt = null;

        try {
            String query = "UPDATE state_log set log_json = JSON_MODIFY(log_json, '$.glucoseReading', ?) WHERE TS IN (SELECT TOP 1 TS FROM state_log WHERE participant_uuid = ? AND JSON_VALUE(log_json, '$.state') = 'startReading' ORDER BY TS DESC)";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query);
            stmt.setString(1, result);
            stmt.setString(2, participantUUID);
            stmt.executeUpdate();

        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
        }
    }

    public String getGlucoseResults(String participantUUID) {
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;
        String result = "";

        try {
            String query = "SELECT TOP 1 JSON_VALUE(log_json, '$.glucoseReading') AS reading FROM state_log WHERE participant_uuid = ? AND JSON_VALUE(log_json, '$.state') = 'startReading' AND JSON_VALUE(log_json, '$.glucoseReading') IS NOT NULL ORDER BY TS DESC";
            conn = ds.getConnection();
            stmt = conn.prepareStatement(query);
            stmt.setString(1, participantUUID);
            rs = stmt.executeQuery();

            if (rs.next()) {
                result = rs.getString("reading");
            }

        } catch (Exception ex) {
            ex.printStackTrace();
        } finally {
            try { rs.close(); }   catch (Exception e) { /* Null Ignored */ }
            try { stmt.close(); } catch (Exception e) { /* Null Ignored */ }
            try { conn.close(); } catch (Exception e) { /* Null Ignored */ }
        }
        return result;
    }
}
