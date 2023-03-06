# SmartState
---

### Components
- Java Engine: Contains the state machine, messaging service APIs, general management (this is the Brain!)
- Rasa: NLU AI used to ask user questions and determine the intent of their messages.
- Website: This is mostly to view state logs, sent/received messages, and for user management.

### Requirements
1. Apache Maven (or similar java compilation manager)
2. A Twilio Account
3. Docker (and docker-compose)
4. SQL Server Management Studio (SSMS)
5. PHP Composer (this is option to install on your host machine, however, we will need to install it in the PHP docker container to generate the necessary files)

### How to make it work
#### JAVA:
1. Navigate to the java/ directory.
2. Run ``` mvn clean package ```
3. Run ``` java -jar ./target/SmartState-1.0-SNAPSHOT.jar ```
4. The application will initialize the state machine and listen for new text messages

#### WEBSITE:
##### Backend (Part 1):
1. Navigate to docker-backend/
2. Check the docker-compose.yml file to change configs that suit your needs. Usually this entails changing the default MSSQL DB password. This is found in ``` SmartState/website/SmartState-backend/sqlserver/Dockerfile ``` and ``` SmartState/website/SmartState-backend/sqlserver/run-initialization.sh ```
3. Run ``` docker-compose up -d ```
(Steps 4-6 are optional if you have PHP composer installed on your host machine)
4. Run ``` docker ps ``` to get the first four characters of the PHP container hash (use it in the next step)
5. Run ``` docker exec -it #### /bin/sh ```
6. Navigate to https://getcomposer.org/download/ and follow the Command-line installation
7. Run ``` composer install ``` while still in the docker container (if there is an error try ``` composer update ```)

##### Backend (Part 2):
1. A database initialization file is provided in SmartState-backend/sqlserver/init.sql, feel free to change this to suit your needs.
2. This script will run automatically on docker-compose, and will take about 30 seconds to complete.

##### Frontend:
1. Navigate to SmartState-frontend/
2. Rename ```config.php.example ``` to ```config.php ```
3. Edit this file with your preferred options. (host is ``` mssql ```, default DB is ``` myDB ```)
4. Navigate to http://localhost:8080/ for web interface. If there are no accounts in the database, the first username and password used to login for the first time will be added as an admin.

MSSQL default config:
- username: SA
- password: Codeman01

#### Rasa:
1. Refer to the README in the rasa/ directory
2. Documentation here: https://rasa.com/docs/

### Workflow
- Using LoRaWAN Door Sensor, we can count the number of times it has been used.
- To signal if a normal, low, or high blood glucose reading has occurred I will mod (%) this number by 3.
- For example:
- - number of times opened = 6 --> 6 % 3 = 0    // low reading
- - number of times opened = 7 --> 7 % 3 = 1    // normal reading
- - number of times opened = 8 --> 8 % 3 = 2    // high reading
