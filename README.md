# SmartState

## Overview
Developing and enforcing study protocols is crucial in medical research, especially as interactions with participants 
become more intricate. Traditional rules-based systems struggle to provide the automation and flexibility required for 
real-time, personalized data collection. We introduce SmartState, a state-based system designed to act as a personal 
agent for each participant, continuously managing and tracking their unique interactions. Unlike traditional reporting 
systems, SmartState enables real-time, automated data collection with minimal oversight. By integrating large language 
models to distill conversations into structured data, SmartState reduces errors and safeguards data integrity through 
built-in protocol and participant auditing. We demonstrate its utility in research trials involving time-dependent 
participant interactions, addressing the increasing need for reliable automation in complex clinical studies.

---
## Requirements
Ensure you have the following installed:
1. **Apache Maven** – For Java compilation and package management.
2. **Twilio Account** – For messaging services.
3. **Docker & Docker Compose** – For containerized deployment.
4. **PostgreSQL** – For database management.
5. **PHP Composer** (Optional) – Required within the PHP Docker container for dependency management.

---
## Installation & Setup
### Web Interface
#### Backend Setup
1. Copy ```.env.example``` to ```.env``` and update the values.
2. Run:
   ```sh
   docker compose up -d
   ```
3. If PHP Composer is not installed on your host machine, otherwise skip to 4.:
    - Run `docker ps` to get the PHP container ID (first four characters).
    - Access the container:
      ```sh
      docker exec -it <CONTAINER_ID> /bin/sh
      ```
    - Install Composer:
      ```sh
      curl -sS https://getcomposer.org/installer | php
      mv composer.phar /usr/local/bin/composer
      ```
4. Run:
  ```sh
  composer install
  ```

#### Frontend Setup
1. Navigate to `website/SmartState-frontend/`.
2. Rename `config.php.example` to `config.php`.
3. Edit `config.php` and update the values.
4. Access the web interface at:
   ```
   http://localhost:8080/
   ```

### Java Engine
1. Navigate to the `java/` directory.
2. Run:
   ```sh
   mvn clean package
   ```
3. Start the application:
   ```sh
   java -jar ./target/SmartState-1.0-SNAPSHOT.jar
   ```
4. The system will initialize and begin listening for messages.
   
---
## Additional Notes
- Modify configurations as needed to suit your requirements.
- Ensure all dependencies are correctly installed before starting services.

For further assistance, check the respective documentation of each component.

---

