FROM mcr.microsoft.com/mssql/server:latest
ENV MSSQL_PID "Standard"
ENV SA_PASSWORD "Codeman01" 
ENV ACCEPT_EULA "Y"
ENV TZ "America/New_York"

USER root
RUN mkdir -p /opt/app
WORKDIR /opt/app
COPY . /opt/app
RUN chmod +x /opt/app/run-initialization.sh

CMD /bin/bash ./entrypoint.sh