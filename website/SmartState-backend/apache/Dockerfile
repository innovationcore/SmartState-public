FROM httpd:2.4.43-alpine
RUN apk update; \
    apk upgrade;
COPY demo.apache.conf /usr/local/apache2/conf/demo.apache.conf
COPY my-httpd.conf /usr/local/apache2/conf/httpd.conf
COPY mycert.key /etc/ssl/private/mycert.key
COPY mycert.crt /etc/ssl/certs/mycert.crt

RUN httpd -k restart