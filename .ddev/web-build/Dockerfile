#ddev-silent-no-warn
# proxy the Vite dev server at /vite-dev/ — see apache-vite.conf
RUN a2enmod proxy proxy_http proxy_wstunnel
COPY apache-vite.conf /etc/apache2/conf-enabled/vite.conf
