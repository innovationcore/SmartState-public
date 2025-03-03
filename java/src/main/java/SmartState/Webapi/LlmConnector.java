package SmartState.Webapi;

import SmartState.Launcher;
import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;

public class LlmConnector {
    private static final String API_URL = Launcher.config.getStringParam("llm_api_url");
    private static final String API_KEY = Launcher.config.getStringParam("llm_api_key");

    public JSONObject query(JSONArray messages) {
        try {
            // Create JSON request body
            JSONObject json = new JSONObject();
            json.put("model", "");
            json.put("messages", messages);
            json.put("max_tokens", 500);
            json.put("temperature", 0.5);

            // Set up HTTP connection
            URL url = new URL(API_URL);
            HttpURLConnection connection = (HttpURLConnection) url.openConnection();
            connection.setRequestMethod("POST");
            connection.setRequestProperty("Content-Type", "application/json");
            connection.setRequestProperty("Authorization", "Bearer " + API_KEY);
            connection.setDoOutput(true);

            // Send JSON request body
            try (OutputStream os = connection.getOutputStream()) {
                byte[] input = json.toString().getBytes("utf-8");
                os.write(input, 0, input.length);
            }

            // Get HTTP response code
            int statusCode = connection.getResponseCode();
//            System.out.println("HTTP Status Code: " + statusCode);

            // Read and log response
            StringBuilder response = new StringBuilder();
            try (BufferedReader br = new BufferedReader(
                    new InputStreamReader(
                            (statusCode == 200) ? connection.getInputStream() : connection.getErrorStream(),
                            "utf-8"))) {
                String responseLine;
                while ((responseLine = br.readLine()) != null) {
                    response.append(responseLine.trim());
                }
            }

            // If status code is not 200, log and return an error
            if (statusCode != 200) {
                System.out.println("Error: Received HTTP " + statusCode);
                return new JSONObject().put("error",response.toString());
            }

            // Parse and return response from OpenAI
            JSONObject jsonResponse = new JSONObject(response.toString());
            JSONObject result = jsonResponse.getJSONArray("choices").getJSONObject(0).getJSONObject("message"); //.getString("message").trim();
            return result;

        } catch (Exception e) {
            // Log the exception stack trace
            System.out.println("An exception occurred:");
            e.printStackTrace();
            return new JSONObject().put("error", "Unable to complete request due to an exception.");
        }
    }
}
