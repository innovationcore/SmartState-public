# Wait to be sure that SQL Server came up
sleep 30s

# Run the init.sql script to create the DB and the schema in the DB
/opt/mssql-tools/bin/sqlcmd -S localhost -U SA -P Codeman01 -i init.sql